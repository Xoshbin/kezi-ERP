<?php

namespace App\Services;

use App\Events\InvoiceConfirmed;
use App\Exceptions\DeletionNotAllowedException;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\User;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Actions\Accounting\CreateJournalEntryForInvoiceAction;

class InvoiceService
{
    public function __construct(protected JournalEntryService $journalEntryService)
    {
    }

    public function create(array $data): Invoice
    {
        $company = Company::findOrFail($data['company_id']);
        if ($company->isDateLocked($data['invoice_date'])) {
            throw new \App\Exceptions\PeriodIsLockedException('The period for this invoice date is locked.');
        }

        $validatedData = Validator::make($data, [
            'company_id' => 'required|exists:companies,id',
            'customer_id' => 'required|exists:partners,id',
            'currency_id' => 'required|exists:currencies,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date',
            'lines' => 'sometimes|array',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric|min:0',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.income_account_id' => 'required|exists:accounts,id',
        ])->validate();

        return DB::transaction(function () use ($validatedData, $data) {
            $invoice = new Invoice();
            $invoice->fill(collect($validatedData)->except('lines')->all());
            $invoice->status = Invoice::TYPE_DRAFT;

            $currencyCode = Currency::find($data['currency_id'])->code;
            $lines = $data['lines'] ?? [];

            // Temporarily hold lines to calculate totals before saving
            // MODIFIED: Use Money objects for calculation
            $totalAmount = Money::of(0, $currencyCode);
            foreach ($lines as $line) {
                $lineTotal = Money::of($line['unit_price'], $currencyCode)->multipliedBy($line['quantity']);
                $totalAmount = $totalAmount->plus($lineTotal);
            }
            $invoice->total_amount = $totalAmount;
            $invoice->total_tax = Money::of(0, $currencyCode); // Assuming no tax for now, can be enhanced later

            $invoice->save(); // Now save with totals

            // MODIFIED: Create lines one by one to ensure the cast can handle the Money object
            if (!empty($lines)) {
                foreach ($lines as $lineData) {
                    // MODIFIED: Manually create the Money object before passing it to the model layer.
                    // This ensures the MoneyCast receives an object and doesn't need to resolve the currency.
                    $lineData['unit_price'] = Money::of($lineData['unit_price'], $currencyCode);
                    $invoice->invoiceLines()->create($lineData);
                }
            }

            // Recalculate to be absolutely sure and handle taxes if logic is added
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

        // Validate the incoming data before starting a transaction.
        $validatedData = Validator::make($data, [
            'partner_id' => 'sometimes|exists:partners,id',
            'currency_id' => 'sometimes|exists:currencies,id',
            'invoice_date' => 'sometimes|date',
            'due_date' => 'sometimes|date',
            'lines' => 'sometimes|array',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.quantity' => 'required|numeric|min:0',
            'lines.*.unit_price' => 'required|numeric|min:0',
        ])->validate();


        return DB::transaction(function () use ($invoice, $validatedData) {
            // If new lines are provided, replace the old ones.
            if (isset($validatedData['lines'])) {
                $invoice->invoiceLines()->delete();
                // MODIFIED: Create lines one by one to ensure the cast can handle the Money object
                foreach ($validatedData['lines'] as $lineData) {
                    $invoice->invoiceLines()->create($lineData);
                }
            }

            // Update other fields like description, due_date, etc.
            $invoice->update(
                collect($validatedData)->except('lines')->all()
            );

            // Always recalculate totals to ensure they are correct after any change.
            $this->recalculateInvoiceTotals($invoice);
            $invoice->save(); // Save the new totals.

            return true;
        });
    }

    public function recalculateInvoiceTotals(Invoice $invoice): void
    {
        $invoice->loadMissing('invoiceLines'); // Ensure lines are loaded
        $currencyCode = $invoice->currency->code;

        // MODIFIED: Use Money objects and a loop for accurate summation
        $subtotal = Money::of(0, $currencyCode);
        $tax = Money::of(0, $currencyCode);

        foreach ($invoice->invoiceLines as $line) {
            $subtotal = $subtotal->plus($line->subtotal);
            $tax = $tax->plus($line->total_line_tax);
        }

        $invoice->total_amount = $subtotal->plus($tax);
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

        if ($invoice->company->isDateLocked($invoice->invoice_date)) {
            throw new \App\Exceptions\PeriodIsLockedException('The period for this invoice date is locked.');
        }

        DB::transaction(function () use ($invoice, $user) {
            $this->recalculateInvoiceTotals($invoice);
            $invoice->invoice_number = $this->getNextInvoiceNumber($invoice->company);
            $invoice->status = Invoice::TYPE_POSTED;
            $invoice->posted_at = now();

            $journalEntry = (new CreateJournalEntryForInvoiceAction())->execute($invoice, $user);
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

    public function resetToDraft(Invoice $invoice, User $user, string $reason): void
    {
        // 1. Authorize the action using a Policy.
        Gate::forUser($user)->authorize('resetToDraft', $invoice);

        DB::transaction(function () use ($invoice, $user, $reason) {
            // 2. Delete the associated Journal Entry to reverse the financial impact.
            if ($invoice->journalEntry) { // Check if it exists before trying to delete
                $invoice->journalEntry->delete();
            }

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
