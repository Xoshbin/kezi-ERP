<?php

namespace App\Services;

use App\Actions\Accounting\CreateJournalEntryForInvoiceAction;
use App\Actions\Sales\CreateStockMovesForInvoiceAction;
use App\DataTransferObjects\Sales\CreateStockMovesForInvoiceDTO;
use App\Enums\Sales\InvoiceStatus;
use App\Events\InvoiceConfirmed;
use App\Exceptions\DeletionNotAllowedException;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\User; // Add this import
use App\Services\Accounting\LockDateService;
use App\Actions\Accounting\BuildInvoicePostingPreviewAction;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class InvoiceService
{
    public function __construct(
        protected LockDateService $lockDateService,
        protected JournalEntryService $journalEntryService,
        protected CreateJournalEntryForInvoiceAction $createJournalEntryForInvoiceAction,
        protected CreateStockMovesForInvoiceAction $createStockMovesForInvoiceAction,
        protected SequenceService $sequenceService,
        protected CurrencyConverterService $currencyConverter,
        protected ExchangeRateService $exchangeRateService
    ) {}

    public function delete(Invoice $invoice): bool
    {
        // Guard Clause: Only allow deleting if the status is InvoiceStatus::Draft.
        if ($invoice->status !== InvoiceStatus::Draft) {
            throw new DeletionNotAllowedException('Cannot delete a posted invoice.');
        }

        // If the guard passes, proceed with the deletion.
        $result = $invoice->delete();

        return $result !== null ? $result : false;
    }

    public function confirm(Invoice $invoice, User $user): void
    {
        // Guard clause to prevent re-confirming.
        if ($invoice->status !== InvoiceStatus::Draft) {
            // Or throw a custom exception
            return;
        }

        $this->lockDateService->enforce(Company::findOrFail($invoice->company_id), Carbon::parse($invoice->invoice_date));

        // Validate business rules before posting
        $this->validateInvoiceForPosting($invoice);

        DB::transaction(function () use ($invoice, $user) {
            // Process multi-currency amounts before posting
            $this->processMultiCurrencyAmounts($invoice);

            $invoice->invoice_number = $this->sequenceService->getNextInvoiceNumber($invoice->company, Carbon::parse($invoice->invoice_date));
            $invoice->status = InvoiceStatus::Posted;
            $invoice->posted_at = now();

            $journalEntry = $this->createJournalEntryForInvoiceAction->execute($invoice, $user);
            $invoice->journal_entry_id = $journalEntry->id;

            $invoice->save();

            // Create stock moves for storable products only if:
            // Invoice is not linked to a sales order (sales orders handle their own deliveries)
            // Note: Unlike vendor bills, invoices always create stock moves regardless of inventory mode
            // for proper lot tracking, FEFO allocation, and inventory management
            if (!$invoice->sales_order_id) {
                $this->createStockMovesForInvoiceAction->execute(
                    new CreateStockMovesForInvoiceDTO($invoice, $user)
                );
            }

            InvoiceConfirmed::dispatch($invoice);
        });
    }

    /**
     * Resets a posted invoice back to draft status with a detailed audit log.
     */
    public function resetToDraft(Invoice $invoice, User $user, string $reason): void
    {
        if ($invoice->status !== InvoiceStatus::Posted) {
            throw new Exception('Only posted invoices can be reset to draft.');
        }

        DB::transaction(function () use ($invoice, $user, $reason) {
            $originalEntry = $invoice->journalEntry;
            if (! $originalEntry) {
                throw new Exception('Cannot reset an invoice without a journal entry.');
            }

            // Step 1: Create a detailed audit log explaining the action.
            AuditLog::create([
                'user_id' => $user->id,
                'event_type' => 'reset_to_draft',
                'auditable_type' => get_class($invoice),
                'auditable_id' => $invoice->id,
                'description' => 'Invoice Reset to Draft: ' . $reason,
                'old_values' => ['status' => $invoice->status],
                'new_values' => ['status' => InvoiceStatus::Draft],
                'ip_address' => request()->ip(),
            ]);

            // Step 2: Use the service to create the reversing journal entry.
            $this->journalEntryService->createReversal(
                $originalEntry,
                'Reset to Draft of Invoice ' . $invoice->invoice_number . ': ' . $reason,
                $user
            );

            // Step 3: Store original values before clearing them
            $originalInvoiceNumber = $invoice->invoice_number;
            $originalPostedAt = $invoice->posted_at;

            // Step 4: Add to reset log for audit trail
            $resetLog = $invoice->reset_to_draft_log ?? [];
            $resetLog[] = [
                'reset_at' => now()->toISOString(),
                'reset_by' => $user->id,
                'reason' => $reason,
                'original_invoice_number' => $originalInvoiceNumber,
                'original_posted_at' => $originalPostedAt?->toISOString(),
            ];

            // Step 5: Update the invoice's status and clear posted fields.
            $invoice->status = InvoiceStatus::Draft;
            $invoice->posted_at = null;
            $invoice->journal_entry_id = null;
            $invoice->invoice_number = null;
            $invoice->reset_to_draft_log = $resetLog;

            $invoice->save();
        });
    }

    /**
     * Cancels a posted invoice by creating a reversing journal entry and a detailed audit log.
     */
    public function cancel(Invoice $invoice, User $user, string $reason): void
    {
        Gate::forUser($user)->authorize('cancel', $invoice); // You may want a specific policy for this

        if ($invoice->status !== InvoiceStatus::Posted) {
            throw new Exception('Only posted invoices can be cancelled.');
        }

        DB::transaction(function () use ($invoice, $user, $reason) {
            $originalEntry = $invoice->journalEntry;
            if (! $originalEntry) {
                throw new Exception('Cannot cancel an invoice without a journal entry.');
            }

            // Step 1: Create a detailed audit log explaining the action.
            AuditLog::create([
                'user_id' => $user->id,
                'event_type' => 'cancellation',
                'auditable_type' => get_class($invoice),
                'auditable_id' => $invoice->id,
                'description' => 'Invoice Cancelled: ' . $reason,
                'old_values' => ['status' => $invoice->status],
                'new_values' => ['status' => InvoiceStatus::Cancelled],
                'ip_address' => request()->ip(),
            ]);

            // Step 2: Use the service to create the reversing journal entry.
            $this->journalEntryService->createReversal(
                $originalEntry,
                'Cancellation of Invoice ' . $invoice->invoice_number . ': ' . $reason,
                $user
            );

            // Step 3: Update the invoice's status to reflect the cancellation.
            $invoice->status = InvoiceStatus::Cancelled;
            $invoice->save();
        });
    }

    /**
     * Process multi-currency amounts for an invoice.
     * Captures exchange rate and converts amounts to company base currency.
     */
    protected function processMultiCurrencyAmounts(Invoice $invoice): void
    {
        // Load necessary relationships
        $invoice->load(['company', 'currency', 'invoiceLines']);

        // If invoice is in company base currency, set rate to 1.0
        if ($invoice->currency_id === $invoice->company->currency_id) {
            $invoice->update([
                'exchange_rate_at_creation' => 1.0,
                'total_amount_company_currency' => $invoice->total_amount,
                'total_tax_company_currency' => $invoice->total_tax,
            ]);

            return;
        }

        // Use manually set exchange rate if available, otherwise get from currency converter
        $exchangeRate = $invoice->exchange_rate_at_creation;

        if (! $exchangeRate) {
            // Get exchange rate for the invoice date
            $exchangeRate = $this->currencyConverter->getExchangeRate($invoice->currency, $invoice->invoice_date, $invoice->company);

            // If no exchange rate is found, try to get the latest available rate as fallback
            if (! $exchangeRate) {
                $exchangeRate = $this->currencyConverter->getLatestExchangeRate($invoice->currency, $invoice->company);

                // If still no rate found, skip multi-currency processing for backward compatibility
                if (! $exchangeRate) {
                    $invoice->exchange_rate_at_creation = 1.0;
                    $invoice->total_amount_company_currency = $invoice->total_amount;
                    $invoice->total_tax_company_currency = $invoice->total_tax;

                    return;
                }
            }
        }

        // Convert amounts to company currency using the exchange rate
        $companyCurrency = $invoice->company->currency;

        $totalAmountCompanyCurrency = $this->currencyConverter->convertWithRate(
            $invoice->total_amount,
            $exchangeRate,
            $companyCurrency->code,
            false
        );

        $totalTaxCompanyCurrency = $this->currencyConverter->convertWithRate(
            $invoice->total_tax,
            $exchangeRate,
            $companyCurrency->code,
            false
        );

        // Convert invoice line amounts
        foreach ($invoice->invoiceLines as $line) {
            $this->convertInvoiceLineAmounts($line, $companyCurrency, $exchangeRate);
        }

        // Update invoice with converted amounts
        $invoice->update([
            'exchange_rate_at_creation' => $exchangeRate,
            'total_amount_company_currency' => $totalAmountCompanyCurrency,
            'total_tax_company_currency' => $totalTaxCompanyCurrency,
        ]);
    }

    /**
     * Convert invoice line amounts to company currency.
     *
     * @param  \App\Models\InvoiceLine  $line
     */
    protected function convertInvoiceLineAmounts($line, Currency $companyCurrency, float $exchangeRate): void
    {
        $unitPriceCompanyCurrency = $this->currencyConverter->convertWithRate(
            $line->unit_price,
            $exchangeRate,
            $companyCurrency->code,
            false
        );

        $subtotalCompanyCurrency = $this->currencyConverter->convertWithRate(
            $line->subtotal,
            $exchangeRate,
            $companyCurrency->code,
            false
        );

        $totalLineTaxCompanyCurrency = $this->currencyConverter->convertWithRate(
            $line->total_line_tax,
            $exchangeRate,
            $companyCurrency->code,
            false
        );

        $line->update([
            'unit_price_company_currency' => $unitPriceCompanyCurrency,
            'subtotal_company_currency' => $subtotalCompanyCurrency,
            'total_line_tax_company_currency' => $totalLineTaxCompanyCurrency,
        ]);
    }

    /**
     * Validate invoice before posting to ensure all required data is present.
     *
     * @throws RuntimeException if validation fails
     */
    private function validateInvoiceForPosting(Invoice $invoice): void
    {
        $preview = app(BuildInvoicePostingPreviewAction::class)->execute($invoice);

        if (! empty($preview['errors'])) {
            // Priority order for error handling
            $errorPriority = [
                'no_line_items',
                'zero_total_amount',
                'income_account_missing',
                'tax_account_missing',
            ];

            foreach ($errorPriority as $errorType) {
                foreach ($preview['issues'] as $issue) {
                    if (($issue['type'] ?? null) === $errorType) {
                        throw new RuntimeException($issue['message']);
                    }
                }
            }

            // Fallback to first error message if none matched priority
            throw new RuntimeException($preview['errors'][0]);
        }
    }
}
