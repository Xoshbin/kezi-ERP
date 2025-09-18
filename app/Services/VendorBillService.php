<?php

namespace App\Services;

use App\Actions\Accounting\BuildVendorBillPostingPreviewAction;
use App\Actions\Accounting\CreateJournalEntryForVendorBillAction;
use App\Actions\Inventory\CreateStockMoveAction;
use App\DataTransferObjects\Inventory\CreateStockMoveDTO;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Products\ProductType;
use App\Enums\Purchases\VendorBillStatus;
use App\Events\VendorBillConfirmed;
use App\Exceptions\DeletionNotAllowedException;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Currency;
use App\Models\User;
use App\Models\VendorBill;
use App\Models\VendorBillLine;
use App\Models\StockMove;

use App\Services\Accounting\LockDateService;
use App\Events\Inventory\StockMoveConfirmed;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class VendorBillService
{
    public function __construct(
        protected LockDateService $lockDateService,
        protected JournalEntryService $journalEntryService,
        protected CreateStockMoveAction $createStockMoveAction,
        protected CurrencyConverterService $currencyConverter,
        protected ExchangeRateService $exchangeRateService,
        protected SequenceService $sequenceService
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

            // Always create stock moves for storable product lines
            /** @var VendorBillLine $line */
            foreach ($vendorBill->lines()->with('product')->get() as $line) {
                if ($line->product?->type === ProductType::Storable) {
                    $this->createStockMoveForLine($vendorBill, $line, $user);
                }
            }

            // Create a single combined JE for all lines (storable, asset, expense)
            $journalEntry = app(CreateJournalEntryForVendorBillAction::class)->execute($vendorBill, $user);

            // Associate the created journal entry with the bill
            // Journal entry is always created
            $vendorBill->update(['journal_entry_id' => $journalEntry->getKey()]);
        });

        VendorBillConfirmed::dispatch($vendorBill, $user);
    }

    /**
     * Creates a stock move for a given vendor bill line.
     */
    private function createStockMoveForLine(VendorBill $vendorBill, VendorBillLine $line, User $user): void
    {
        $company = $vendorBill->company;

        if (! $company->vendorLocation || ! $company->defaultStockLocation) {
            throw new RuntimeException("Default Vendor or Stock Location is not configured for Company ID: {$company->getKey()}.");
        }

        if (! $line->product_id) {
            throw new \Exception('Vendor bill line must have a product to create stock move');
        }

        // Idempotency guard: avoid duplicate moves for the same bill line
        $exists = StockMove::query()
            ->where('company_id', $company->getKey())
            ->where('product_id', $line->product_id)
            ->where('from_location_id', $company->vendorLocation->getKey())
            ->where('to_location_id', $company->defaultStockLocation->getKey())
            ->where('source_type', VendorBill::class)
            ->where('source_id', $vendorBill->getKey())
            ->exists();

        if ($exists) {
            return;
        }

        $dto = new CreateStockMoveDTO(
            company_id: $company->getKey(),
            product_id: $line->product_id,
            quantity: (float) $line->quantity,
            from_location_id: $company->vendorLocation->getKey(),
            to_location_id: $company->defaultStockLocation->getKey(),
            move_type: StockMoveType::Incoming,
            status: StockMoveStatus::Done, // Moves from bills are immediately 'done'
            move_date: $vendorBill->bill_date,
            reference: $vendorBill->bill_reference,
            source_type: VendorBill::class,
            source_id: $vendorBill->getKey(),
            created_by_user_id: $user->id
        );

        $stockMove = $this->createStockMoveAction->execute($dto);

        // Immediately dispatch confirmation to trigger valuation processing
        StockMoveConfirmed::dispatch($stockMove);
    }

    /**
     * Delete a draft vendor bill.
     */
    public function delete(VendorBill $vendorBill): bool
    {
        $this->lockDateService->enforce(Company::findOrFail($vendorBill->company_id), Carbon::parse($vendorBill->bill_date));

        if ($vendorBill->status !== VendorBillStatus::Draft) {
            throw new DeletionNotAllowedException(
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
                'description' => 'Vendor Bill Cancelled: ' . $reason,
                'old_values' => ['status' => $vendorBill->status],
                'new_values' => ['status' => VendorBillStatus::Cancelled],
                'ip_address' => request()->ip(),
            ]);

            // Step 2: Create the proper reversing journal entry.
            // The "reason" is passed to the reversal for the entry's description.
            $this->journalEntryService->createReversal(
                $originalEntry,
                'Cancellation of Bill ' . $vendorBill->bill_reference . ': ' . $reason,
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
            $vendorBill->exchange_rate_at_creation = 1.0;
            $vendorBill->total_amount_company_currency = $vendorBill->total_amount;
            $vendorBill->total_tax_company_currency = $vendorBill->total_tax;

            return;
        }

        // Get exchange rate for the bill date
        $exchangeRate = $this->currencyConverter->getExchangeRate($vendorBill->currency, $vendorBill->bill_date, $vendorBill->company);

        // If no exchange rate is found, skip multi-currency processing for backward compatibility
        if (! $exchangeRate) {
            $vendorBill->exchange_rate_at_creation = 1.0;
            $vendorBill->total_amount_company_currency = $vendorBill->total_amount;
            $vendorBill->total_tax_company_currency = $vendorBill->total_tax;

            return;
        }

        // Convert amounts to company currency
        $companyCurrency = $vendorBill->company->currency;

        $totalAmountCompanyCurrency = $this->currencyConverter->convertToBaseCurrency(
            $vendorBill->total_amount,
            $vendorBill->currency,
            $companyCurrency,
            $vendorBill->bill_date,
            $vendorBill->company
        );

        $totalTaxCompanyCurrency = $this->currencyConverter->convertToBaseCurrency(
            $vendorBill->total_tax,
            $vendorBill->currency,
            $companyCurrency,
            $vendorBill->bill_date,
            $vendorBill->company
        );

        // Convert vendor bill line amounts
        foreach ($vendorBill->lines as $line) {
            $this->convertVendorBillLineAmounts($line, $companyCurrency, $vendorBill->company);
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
     * @param  \App\Models\VendorBillLine  $line
     */
    protected function convertVendorBillLineAmounts($line, Currency $companyCurrency, Company $company): void
    {
        $unitPriceCompanyCurrency = $this->currencyConverter->convertToBaseCurrency(
            $line->unit_price,
            $line->vendorBill->currency,
            $companyCurrency,
            $line->vendorBill->bill_date,
            $company
        );

        $subtotalCompanyCurrency = $this->currencyConverter->convertToBaseCurrency(
            $line->subtotal,
            $line->vendorBill->currency,
            $companyCurrency,
            $line->vendorBill->bill_date,
            $company
        );

        $totalLineTaxCompanyCurrency = $this->currencyConverter->convertToBaseCurrency(
            $line->total_line_tax,
            $line->vendorBill->currency,
            $companyCurrency,
            $line->vendorBill->bill_date,
            $company
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
                $this->journalEntryService->createReversal($originalEntry, 'Reset of Bill ' . $vendorBill->bill_reference . ': ' . $reason, $user);
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

        if (!empty($preview['errors'])) {
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
}
