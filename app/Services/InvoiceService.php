<?php

namespace App\Services;

use App\Events\InvoiceConfirmed;
use App\Exceptions\DeletionNotAllowedException;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class InvoiceService
{
    public function __construct(protected JournalEntryService $journalEntryService)
    {
    }

    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $invoice = new Invoice();
            $invoice->fill(collect($data)->except('lines')->all());
            $invoice->status = Invoice::TYPE_DRAFT;
            $invoice->total_amount = 0;
            $invoice->total_tax = 0;
            $invoice->save(); // Save the invoice first to get an ID

            if (isset($data['lines'])) {
                $invoice->invoiceLines()->createMany($data['lines']);
            }

            $this->recalculateInvoiceTotals($invoice);
            $invoice->save();

            return $invoice;
        });
    }

    public function update(Invoice $invoice, array $data): bool
    {
        // Guard Clause: Never allow updating a posted invoice.
        if ($invoice->status !== Invoice::TYPE_DRAFT) {
            throw new UpdateNotAllowedException('Cannot modify a non-draft invoice.');
        }

        return DB::transaction(function () use ($invoice, $data) {
            // If new lines are provided, replace the old ones.
            if (isset($data['lines'])) {
                $invoice->invoiceLines()->delete();
                $invoice->invoiceLines()->createMany($data['lines']);
            }

            // Always recalculate totals to ensure they are correct.
            $this->recalculateInvoiceTotals($invoice);

            // Update other fields like description, due_date, etc.
            return $invoice->update(
                collect($data)->except('lines')->all()
            );
        });
    }

    public function recalculateInvoiceTotals(Invoice $invoice): void
    {
        $invoice->load('invoiceLines'); // Ensure lines are loaded

        $subtotal = $invoice->invoiceLines->sum('subtotal'); // Assuming subtotal is calculated on the line
        $tax = $invoice->invoiceLines->sum('total_line_tax'); // Assuming tax is calculated on the line

        $invoice->total_amount = $subtotal + $tax;
        $invoice->total_tax = $tax;
    }

    public function delete(Invoice $invoice): bool
    {
        // Guard Clause: Only allow deleting if the status is Invoice::TYPE_DRAFT.
        if ($invoice->status !== Invoice::TYPE_DRAFT) {
            throw new DeletionNotAllowedException('Cannot delete a posted invoice.');
        }

        // If the guard passes, proceed with the deletion.
        return $invoice->delete();
    }

    public function confirm(Invoice $invoice, User $user): void
    {
        // Guard clause to prevent re-confirming.
        if ($invoice->status !== Invoice::TYPE_DRAFT) {
            // Or throw a custom exception
            return;
        }

        DB::transaction(function () use ($invoice, $user) {
            $this->recalculateInvoiceTotals($invoice);
            $invoice->invoice_number = $this->getNextInvoiceNumber($invoice->company);
            $invoice->status = Invoice::TYPE_POSTED;
            $invoice->posted_at = now();

            $journalEntry = $this->createJournalEntryForInvoice($invoice, $user);
            $invoice->journal_entry_id = $journalEntry->id;

            $invoice->save();

            InvoiceConfirmed::dispatch($invoice);
        });
    }

    private function getNextInvoiceNumber(Company $company): string
    {
        // Simple (but not race-condition-proof) way to get the next number.
        $lastNumber = Invoice::where('company_id', $company->id)
            ->whereNotNull('invoice_number')
            ->count();

        // Format it nicely, e.g., INV-00001
        return 'INV-' . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Prepares the data for and creates the journal entry for a posted invoice.
     */
    // In app/Services/InvoiceService.php
    private function createJournalEntryForInvoice(Invoice $invoice, User $user): JournalEntry
    {
        // Load the relationships we'll need
        $invoice->load('company.currency', 'currency', 'invoiceLines.tax');

        $company = $invoice->company;
        $baseCurrency = $company->currency;
        $foreignCurrency = $invoice->currency;
        $arAccountId = $company->default_accounts_receivable_id;
        $salesJournalId = $company->default_sales_journal_id;

        if (!$arAccountId || !$salesJournalId) {
            throw new \RuntimeException('Default accounts receivable or sales journal is not configured for this company.');
        }

        // Determine the exchange rate. If it's the same currency, the rate is 1.
        $exchangeRate = ($baseCurrency->id === $foreignCurrency->id) ? 1 : $foreignCurrency->exchange_rate;

        $lines = [];

        // 1. The Debit Line (Total amount converted to base currency)
        $lines[] = [
            'account_id' => $arAccountId,
            'debit' => $invoice->total_amount * $exchangeRate, // Convert to base currency
            'description' => 'A/R for ' . $invoice->invoice_number,
            // Add foreign currency details for reference
            'currency_id' => $foreignCurrency->id,
            'original_currency_amount' => $invoice->total_amount,
            'exchange_rate_at_transaction' => $exchangeRate,
        ];

        // 2. The Credit Lines
        foreach ($invoice->invoiceLines as $line) {
            $creditAccountId = $line->deferred_revenue_account_id ?? $line->income_account_id;
            // Credit the income account
            $lines[] = [
                'account_id' => $creditAccountId,
                'credit' => $line->subtotal * $exchangeRate, // Convert to base currency
                'description' => $line->description,
                // Add foreign currency details
                'currency_id' => $foreignCurrency->id,
                'original_currency_amount' => $line->subtotal,
                'exchange_rate_at_transaction' => $exchangeRate,
            ];

            // Credit the tax account
            if ($line->total_line_tax > 0 && $line->tax) {
                $lines[] = [
                    'account_id' => $line->tax->tax_account_id,
                    'credit' => $line->total_line_tax * $exchangeRate, // Convert to base currency
                    'description' => 'Tax for ' . $invoice->invoice_number,
                    // Add foreign currency details
                    'currency_id' => $foreignCurrency->id,
                    'original_currency_amount' => $line->total_line_tax,
                    'exchange_rate_at_transaction' => $exchangeRate,
                ];
            }
        }

        $journalEntryData = [
            'company_id' => $company->id,
            'journal_id' => $salesJournalId,
            'currency_id' => $baseCurrency->id, // The JE itself is in the base currency
            'entry_date' => $invoice->posted_at,
            'reference' => $invoice->invoice_number,
            'description' => 'Invoice ' . $invoice->invoice_number,
            'source_type' => Invoice::class,
            'source_id' => $invoice->id,
            'lines' => $lines,
            'created_by_user_id' => $user->id,
        ];

        return $this->journalEntryService->create($journalEntryData);
    }
    public function resetToDraft(Invoice $invoice, User $user, string $reason): void
    {
        // 1. Authorize the action using a Policy.
        Gate::forUser($user)->authorize('resetToDraft', $invoice);

        DB::transaction(function () use ($invoice, $user, $reason) {
            // 2. Delete the associated Journal Entry to reverse the financial impact.
            $invoice->journalEntry()->delete();

            // 3. Log this exceptional event.
            $newLog = [
                'user_id' => $user->id,
                'timestamp' => now()->toDateTimeString(),
                'reason' => $reason,
            ];
            // Prepend to existing logs if any.
            $logs = $invoice->reset_to_draft_log ?? [];
            array_unshift($logs, $newLog);

            // 4. Update the invoice state.
            $invoice->update([
                'status' => Invoice::TYPE_DRAFT,
                'journal_entry_id' => null,
                'posted_at' => null,
                'invoice_number' => null, // You should also nullify the number to allow it to be re-sequenced.
                'reset_to_draft_log' => $logs,
            ]);
        });
    }
}
