<?php

namespace App\Services;

// Add new imports for Actions and DTOs
use App\Models\User;
use RuntimeException;
use App\Models\AuditLog;
use App\Models\VendorBill;
use Illuminate\Support\Facades\DB;
use App\Enums\Products\ProductType;

// ... other existing use statements
use App\Events\VendorBillConfirmed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Inventory\StockMoveStatus;
use App\Services\Inventory\StockMoveService;
use App\Exceptions\UpdateNotAllowedException;
use App\Exceptions\DeletionNotAllowedException;
use App\Actions\Inventory\CreateStockMoveAction;
use App\Actions\Purchases\CreateVendorBillAction;
use App\Actions\Purchases\UpdateVendorBillAction;
use App\DataTransferObjects\Inventory\CreateStockMoveDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\UpdateVendorBillDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\DataTransferObjects\Purchases\UpdateVendorBillLineDTO;
use App\Actions\Accounting\CreateJournalEntryForVendorBillAction;

class VendorBillService
{
    public function __construct(
        protected AccountingValidationService $accountingValidationService,
        protected JournalEntryService $journalEntryService,
        protected CreateStockMoveAction $createStockMoveAction
    ) {}

    // ... The confirm, delete, and resetToDraft methods remain unchanged ...
    // ... because they are orchestrating logic, not just creating/updating data.
    // ... The _confirmAndPostBill private method also remains unchanged.

    /**
     * Confirm a draft vendor bill, post it, and create the corresponding journal entry.
     */
    public function confirm(VendorBill $vendorBill, User $user): void
    {
        // Refresh the model instance to get the latest data from the database,
        // including any totals calculated by observers.
        $vendorBill->refresh();

        if ($vendorBill->status !== VendorBill::STATUS_DRAFT) {
            return; // Or throw an exception
        }

        $this->accountingValidationService->checkIfPeriodIsLocked($vendorBill->company_id, $vendorBill->bill_date);

        Gate::forUser($user)->authorize('confirm', $vendorBill);

        DB::transaction(function () use ($vendorBill, $user) {
            $this->_confirmAndPostBill($vendorBill, $user);
        });

        $vendorBill->status = 'posted';
        $vendorBill->posted_at = now();
        $vendorBill->user_id = $user->id; // Make sure user is set on bill
        $vendorBill->save();

        // Dispatch the event with full context
        VendorBillConfirmed::dispatch($vendorBill, $user);
    }

    /**
     * Delete a draft vendor bill.
     */
    public function delete(VendorBill $vendorBill): bool
    {

        $this->accountingValidationService->checkIfPeriodIsLocked($vendorBill->company_id, $vendorBill->bill_date);

        if ($vendorBill->status !== VendorBill::STATUS_DRAFT) {
            throw new DeletionNotAllowedException(
                'Cannot delete a posted vendor bill. Corrections must be made with a new reversal entry.'
            );
        }

        return DB::transaction(function () use ($vendorBill) {
            return $vendorBill->delete();
        });
    }

    /**
     * Cancels a posted vendor bill by creating a reversing journal entry and a detailed audit log.
     */
    public function cancel(VendorBill $vendorBill, User $user, string $reason): void
    {
        Gate::forUser($user)->authorize('cancel', $vendorBill);

        if ($vendorBill->status !== VendorBill::STATUS_POSTED) {
            throw new \Exception('Only posted vendor bills can be cancelled.');
        }

        DB::transaction(function () use ($vendorBill, $user, $reason) {
            $originalEntry = $vendorBill->journalEntry;
            if (!$originalEntry) {
                throw new \Exception('Cannot cancel a bill without a journal entry.');
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
                'new_values' => ['status' => VendorBill::STATUS_CANCELED],
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
            $vendorBill->status = VendorBill::STATUS_CANCELED;
            $vendorBill->save(); // saveQuietly() isn't needed if the observer handles status changes gracefully
        });
    }

    public function post(VendorBill $vendorBill, User $user): void
    {
        if ($vendorBill->status !== 'draft') {
            return;
        }

        DB::transaction(function () use ($vendorBill, $user) {
            // This existing logic is correct.
            $vendorBill->update(['status' => 'posted', 'posted_at' => now()]);
            $journalEntry = $this->journalEntryService->createJournalEntryForVendorBill($vendorBill);
            $vendorBill->update(['journal_entry_id' => $journalEntry->id]);

            // --- 3. Add the logic to create stock moves ---
            foreach ($vendorBill->lines()->with('product')->get() as $line) {
                if ($line->product?->type === ProductType::Storable) {
                    $company = $vendorBill->company;

                    // Ensure default locations are configured for the company
                    if (!$company->vendorLocation || !$company->defaultStockLocation) {
                        throw new RuntimeException("Default Vendor or Stock Location is not configured for Company ID: {$company->id}. Please configure them to proceed.");
                    }

                    $dto = new CreateStockMoveDTO(
                        company_id: $company->id,
                        product_id: $line->product->id,
                        quantity: $line->quantity,
                        from_location_id: $company->vendorLocation->id,
                        to_location_id: $company->defaultStockLocation->id,
                        move_type: StockMoveType::INCOMING,
                        move_date: $vendorBill->bill_date,
                        reference: $vendorBill->bill_reference,
                        source_type: VendorBill::class,
                        source_id: $vendorBill->id,
                        created_by_user_id: $user->id,
                        // Add the missing argument, e.g. status (adjust as needed)
                        status: StockMoveStatus::DRAFT
                    );

                    $this->createStockMoveAction->execute($dto);
                }
            }
        });
    }

    private function _confirmAndPostBill(VendorBill $vendorBill, User $user): void
    {
        Log::info('--- Starting _confirmAndPostBill for VendorBill ID: ' . $vendorBill->id . ' ---'); // <-- Add Log 1

        if ($vendorBill->status !== 'draft') {
            Log::info('VendorBill status is not draft. Exiting.'); // <-- Add Log 2
            return;
        }

        $vendorBill->status = VendorBill::STATUS_POSTED;
        $vendorBill->posted_at = now();

        $journalEntry = (new CreateJournalEntryForVendorBillAction())->execute($vendorBill, $user);
        $vendorBill->journal_entry_id = $journalEntry->id;

        $vendorBill->save();

        Log::info('Journal Entry created. Looping through lines...'); // <-- Add Log 3


        // Create stock moves for storable products
        foreach ($vendorBill->lines()->with('product')->get() as $line) {
            Log::info('Processing Line ID: ' . $line->id . ' for Product ID: ' . $line->product_id); // <-- Add Log 4

            if ($line->product?->type === ProductType::Storable) {
                Log::info('Product is Storable. Creating Stock Move...'); // <-- Add Log 5

                $company = $vendorBill->company;

                if (!$company->vendorLocation || !$company->defaultStockLocation) {
                    throw new RuntimeException("Default Vendor or Stock Location is not configured for Company ID: {$company->id}.");
                } else {
                    Log::info('Product is NOT Storable. Type is: ' . ($line->product?->type->value ?? 'N/A')); // <-- Add Log 6

                }

                $dto = new CreateStockMoveDTO(
                    company_id: $company->id,
                    product_id: $line->product->id,
                    quantity: $line->quantity,
                    from_location_id: $company->vendorLocation->id,
                    to_location_id: $company->defaultStockLocation->id,
                    move_type: StockMoveType::INCOMING,
                    move_date: $vendorBill->bill_date,
                    reference: $vendorBill->bill_reference,
                    source_type: VendorBill::class,
                    source_id: $vendorBill->id,
                    created_by_user_id: $user->id,
                    // Add the missing argument, e.g. status (adjust as needed)
                    status: StockMoveStatus::DRAFT
                );

                $this->createStockMoveAction->execute($dto);
            }
        }

        VendorBillConfirmed::dispatch($vendorBill);
    }
}
