<?php

namespace Modules\Purchase\Services;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Accounting\Actions\Accounting\BuildVendorBillPostingPreviewAction;
use Modules\Accounting\Contracts\VendorBillJournalEntryCreatorContract;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Foundation\Models\AuditLog;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Services\CurrencyConverterService;
use Modules\Foundation\Services\ExchangeRateService;
use Modules\Foundation\Services\SequenceService;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Events\VendorBillConfirmed;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Models\VendorBillLine;
use RuntimeException;

class VendorBillService
{
    public function __construct(
        protected \Modules\Accounting\Services\Accounting\LockDateService $lockDateService,
        protected JournalEntryService $journalEntryService,
        protected VendorBillJournalEntryCreatorContract $vendorBillJournalEntryCreator,
        protected CurrencyConverterService $currencyConverter,
        protected ExchangeRateService $exchangeRateService,
        protected SequenceService $sequenceService,
        protected \Modules\Purchase\Services\ShippingCostAllocationService $shippingCostAllocationService,
    ) {}

    public function post(VendorBill $vendorBill, User $user): void
    {
        if ($vendorBill->status !== VendorBillStatus::Draft) {
            return;
        }

        $this->lockDateService->enforce(Company::findOrFail($vendorBill->company_id), Carbon::parse($vendorBill->bill_date));

        Gate::forUser($user)->authorize('post', $vendorBill);

        // Validate the vendor bill before posting
        $this->validateVendorBillForPosting($vendorBill);

        DB::transaction(function () use ($vendorBill, $user) {
            // Process multi-currency amounts before posting
            $this->processMultiCurrencyAmounts($vendorBill);

            // Generate bill number if not already set
            if (empty($vendorBill->bill_reference)) {
                $vendorBill->bill_reference = $this->sequenceService->getNextVendorBillNumber($vendorBill->company, Carbon::parse($vendorBill->bill_date));
            }

            $vendorBill->user_id = $user->id;
            $vendorBill->status = VendorBillStatus::Posted;
            $vendorBill->posted_at = now();
            $vendorBill->save();

            // Stock moves and inventory journal entries are now created by the Inventory module's
            // CreateStockMovesOnVendorBillConfirmed listener (Event-Driven Architecture)
            // This decouples Purchase from Inventory, improving modularity.

            // Create a single combined JE for all lines (storable, asset, expense)
            $journalEntry = $this->vendorBillJournalEntryCreator->execute($vendorBill, $user);

            // Associate the created journal entry with the bill
            // Journal entry is always created
            $vendorBill->update(['journal_entry_id' => $journalEntry->getKey()]);
        });

        VendorBillConfirmed::dispatch($vendorBill, $user);
    }

    /**
     * Delete a draft vendor bill.
     */
    public function delete(VendorBill $vendorBill): bool
    {
        $this->lockDateService->enforce(Company::findOrFail($vendorBill->company_id), Carbon::parse($vendorBill->bill_date));

        if ($vendorBill->status !== VendorBillStatus::Draft) {
            throw new \Modules\Foundation\Exceptions\DeletionNotAllowedException(
                'Cannot delete a posted vendor bill. Corrections must be made with a new reversal entry.'
            );
        }

        return DB::transaction(function () use ($vendorBill) {
            $result = $vendorBill->delete();

            return $result !== null ? $result : false;
        });
    }

    /**
     * Cancels a posted vendor bill by creating a reversing journal entry and a detailed audit log.
     */
    public function cancel(VendorBill $vendorBill, User $user, string $reason): void
    {
        Gate::forUser($user)->authorize('cancel', $vendorBill);

        if ($vendorBill->status !== VendorBillStatus::Posted) {
            throw new Exception('Only posted vendor bills can be cancelled.');
        }

        DB::transaction(function () use ($vendorBill, $user, $reason) {
            $originalEntry = $vendorBill->journalEntry;
            if (! $originalEntry) {
                throw new Exception('Cannot cancel a bill without a journal entry.');
            }

            // Step 1: Create a detailed audit log *before* making changes.
            // This captures the state of the bill right before cancellation.
            AuditLog::create([
                'user_id' => $user->id,
                'event_type' => 'cancellation', // A more specific event type
                'auditable_type' => get_class($vendorBill),
                'auditable_id' => $vendorBill->id,
                'description' => 'Vendor Bill Cancelled: '.$reason,
                'old_values' => ['status' => $vendorBill->status],
                'new_values' => ['status' => VendorBillStatus::Cancelled],
                'ip_address' => request()->ip(),
            ]);

            // Step 2: Create the proper reversing journal entry.
            // The "reason" is passed to the reversal for the entry's description.
            $this->journalEntryService->createReversal(
                $originalEntry,
                'Cancellation of Bill '.$vendorBill->bill_reference.': '.$reason,
                $user
            );

            // Step 3: Update the vendor bill's status.
            $vendorBill->status = VendorBillStatus::Cancelled;
            $vendorBill->save(); // saveQuietly() isn't needed if the observer handles status changes gracefully
        });
    }

    public function confirm(VendorBill $vendorBill, User $user): void
    {
        $this->post($vendorBill, $user);
    }

    /**
     * Process multi-currency amounts for a vendor bill.
     * Captures exchange rate and converts amounts to company base currency.
     */
    protected function processMultiCurrencyAmounts(VendorBill $vendorBill): void
    {
        // Load necessary relationships
        $vendorBill->load(['company', 'currency', 'lines']);

        // If vendor bill is in company base currency, set rate to 1.0
        if ($vendorBill->currency_id === $vendorBill->company->currency_id) {
            $vendorBill->update([
                'exchange_rate_at_creation' => 1.0,
                'total_amount_company_currency' => $vendorBill->total_amount,
                'total_tax_company_currency' => $vendorBill->total_tax,
            ]);

            return;
        }

        // Use manually set exchange rate if available, otherwise get from currency converter
        $exchangeRate = $vendorBill->exchange_rate_at_creation;

        if (! $exchangeRate) {
            // Get exchange rate for the bill date
            $exchangeRate = $this->currencyConverter->getExchangeRate($vendorBill->currency, $vendorBill->bill_date, $vendorBill->company);

            // If no exchange rate is found, try to get the latest available rate as fallback
            if (! $exchangeRate) {
                $exchangeRate = $this->currencyConverter->getLatestExchangeRate($vendorBill->currency, $vendorBill->company);

                // If still no rate found, skip multi-currency processing for backward compatibility
                if (! $exchangeRate) {
                    $vendorBill->exchange_rate_at_creation = 1.0;
                    $vendorBill->total_amount_company_currency = $vendorBill->total_amount;
                    $vendorBill->total_tax_company_currency = $vendorBill->total_tax;

                    return;
                }
            }
        }

        // Convert amounts to company currency using the exchange rate
        $companyCurrency = $vendorBill->company->currency;

        $totalAmountCompanyCurrency = $this->currencyConverter->convertWithRate(
            $vendorBill->total_amount,
            $exchangeRate,
            $companyCurrency->code,
            false
        );

        $totalTaxCompanyCurrency = $this->currencyConverter->convertWithRate(
            $vendorBill->total_tax,
            $exchangeRate,
            $companyCurrency->code,
            false
        );

        // Convert vendor bill line amounts
        foreach ($vendorBill->lines as $line) {
            $this->convertVendorBillLineAmounts($line, $companyCurrency, $exchangeRate);
        }

        // Update vendor bill with converted amounts
        $vendorBill->update([
            'exchange_rate_at_creation' => $exchangeRate,
            'total_amount_company_currency' => $totalAmountCompanyCurrency,
            'total_tax_company_currency' => $totalTaxCompanyCurrency,
        ]);
    }

    /**
     * Convert vendor bill line amounts to company currency.
     *
     * @param  VendorBillLine  $line
     */
    protected function convertVendorBillLineAmounts($line, Currency $companyCurrency, float $exchangeRate): void
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

    public function resetToDraft(VendorBill $vendorBill, User $user, string $reason): void
    {
        Gate::forUser($user)->authorize('resetToDraft', $vendorBill);

        if ($vendorBill->status !== VendorBillStatus::Posted) {
            throw new Exception('Only posted vendor bills can be reset to draft.');
        }

        DB::transaction(function () use ($vendorBill, $user, $reason) {
            $originalEntry = $vendorBill->journalEntry;
            if ($originalEntry) {
                $this->journalEntryService->createReversal($originalEntry, 'Reset of Bill '.$vendorBill->bill_reference.': '.$reason, $user);
            }

            $logEntry = [
                'reset_by_user_id' => $user->id,
                'reset_at' => now()->toDateTimeString(),
                'reason' => $reason,
                'original_posted_at' => $vendorBill->posted_at?->toDateTimeString(),
                'original_journal_entry_id' => $vendorBill->journal_entry_id,
            ];

            $vendorBill->update([
                'status' => VendorBillStatus::Draft,
                'posted_at' => null,
                'journal_entry_id' => null,
                'reset_to_draft_log' => array_merge($vendorBill->reset_to_draft_log ?? [], [$logEntry]),
            ]);
        });
    }

    /**
     * Validate vendor bill before posting to ensure all required data is present.
     *
     * @throws RuntimeException if validation fails
     */
    private function validateVendorBillForPosting(VendorBill $vendorBill): void
    {
        $preview = app(BuildVendorBillPostingPreviewAction::class)->execute($vendorBill);

        if (! empty($preview['errors'])) {
            // Priority order for error handling
            $errorPriority = [
                'no_line_items',
                'zero_total_amount',
                'inventory_account_missing',
            ];

            // Find the highest priority error
            foreach ($errorPriority as $errorType) {
                foreach ($preview['issues'] as $issue) {
                    if ($issue['type'] === $errorType) {
                        throw new RuntimeException($issue['message']);
                    }
                }
            }

            // If no priority error found, throw the first error
            throw new RuntimeException($preview['errors'][0]);
        }
    }

    /**
     * Validate shipping costs for the vendor bill based on Incoterms.
     */
    public function validateShippingCosts(VendorBill $vendorBill): \Modules\Purchase\DataTransferObjects\Purchases\ShippingCostValidationResult
    {
        return $this->shippingCostAllocationService->validateVendorBillShippingCosts($vendorBill);
    }
}
