<?php

namespace App\Services;

use App\Actions\Accounting\CreateJournalEntryForExpenseBillAction; // Add this import
use App\Actions\Accounting\CreateJournalEntryForInventoryBillAction;
use App\Actions\Inventory\CreateStockMoveAction;
use App\DataTransferObjects\Inventory\CreateStockMoveDTO;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Products\ProductType;
use App\Enums\Purchases\VendorBillStatus;
use App\Events\VendorBillConfirmed;
use App\Exceptions\DeletionNotAllowedException;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\VendorBill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Services\Accounting\LockDateService;

class VendorBillService
{
    public function __construct(
        protected LockDateService $lockDateService,
        protected JournalEntryService $journalEntryService,
        protected CreateStockMoveAction $createStockMoveAction,
        protected CreateJournalEntryForInventoryBillAction $createJournalEntryForInventoryBillAction,
        protected CreateJournalEntryForExpenseBillAction $createJournalEntryForExpenseBillAction // Add this injection
    ) {}

    public function post(VendorBill $vendorBill, User $user): void
    {
        if ($vendorBill->status !== VendorBillStatus::Draft) {
            return;
        }

        $this->lockDateService->enforce(\App\Models\Company::find($vendorBill->company_id), \Carbon\Carbon::parse($vendorBill->bill_date));
        Gate::forUser($user)->authorize('post', $vendorBill);

        DB::transaction(function () use ($vendorBill, $user) {
            $vendorBill->update([
                'status' => VendorBill::STATUS_POSTED,
                'posted_at' => now(),
                'user_id' => $user->id
            ]);

            // Determine if the bill contains storable or only expense items
            $hasStorableItems = $vendorBill->lines()->whereHas('product', fn($q) => $q->where('type', ProductType::Storable))->exists();

            if ($hasStorableItems) {
                // Handle storable items: create stock moves and inventory journal entry
                foreach ($vendorBill->lines()->with('product')->get() as $line) {
                    if ($line->product?->type === ProductType::Storable) {
                        $this->createStockMoveForLine($vendorBill, $line, $user);
                    }
                }
                $journalEntry = $this->createJournalEntryForInventoryBillAction->execute($vendorBill, $user);
            } else {
                // Handle expense-only items: create standard AP journal entry
                $journalEntry = $this->createJournalEntryForExpenseBillAction->execute($vendorBill, $user);
            }

            // Associate the created journal entry with the bill
            if (isset($journalEntry)) {
                 $vendorBill->update(['journal_entry_id' => $journalEntry->id]);
            }
        });

        VendorBillConfirmed::dispatch($vendorBill, $user);
    }

    /**
     * Creates a stock move for a given vendor bill line.
     */
    private function createStockMoveForLine(VendorBill $vendorBill, \App\Models\VendorBillLine $line, User $user): void
    {
        $company = $vendorBill->company;

        if (!$company->vendorLocation || !$company->defaultStockLocation) {
            throw new RuntimeException("Default Vendor or Stock Location is not configured for Company ID: {$company->id}.");
        }

        $dto = new CreateStockMoveDTO(
            company_id: $company->id,
            product_id: $line->product_id,
            quantity: $line->quantity,
            from_location_id: $company->vendorLocation->id,
            to_location_id: $company->defaultStockLocation->id,
            move_type: StockMoveType::INCOMING,
            status: StockMoveStatus::DONE, // Moves from bills are immediately 'done'
            move_date: $vendorBill->bill_date,
            reference: $vendorBill->bill_reference,
            source_type: VendorBill::class,
            source_id: $vendorBill->id,
            created_by_user_id: $user->id
        );

        $this->createStockMoveAction->execute($dto);
    }

    /**
     * Delete a draft vendor bill.
     */
    public function delete(VendorBill $vendorBill): bool
    {
        $this->lockDateService->enforce(\App\Models\Company::find($vendorBill->company_id), \Carbon\Carbon::parse($vendorBill->bill_date));

        if ($vendorBill->status !== VendorBillStatus::Draft) {
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

        if ($vendorBill->status !== VendorBillStatus::Posted) {
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
}
