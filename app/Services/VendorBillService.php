<?php

namespace App\Services;

// Add new imports for Actions and DTOs
use App\Actions\Purchases\CreateVendorBillAction;
use App\Actions\Purchases\UpdateVendorBillAction;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\DataTransferObjects\Purchases\UpdateVendorBillDTO;
use App\DataTransferObjects\Purchases\UpdateVendorBillLineDTO;

// ... other existing use statements
use App\Actions\Accounting\CreateJournalEntryForVendorBillAction;
use App\Events\VendorBillConfirmed;
use App\Exceptions\DeletionNotAllowedException;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\VendorBill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VendorBillService
{
    public function __construct(
        protected AccountingValidationService $accountingValidationService,
        protected JournalEntryService $journalEntryService
    ) {
    }

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

    private function _confirmAndPostBill(VendorBill $vendorBill, User $user): void
    {
        $vendorBill->status = VendorBill::STATUS_POSTED;
        $vendorBill->posted_at = now();

        $journalEntry = (new CreateJournalEntryForVendorBillAction())->execute($vendorBill, $user);
        $vendorBill->journal_entry_id = $journalEntry->id;

        $vendorBill->save();

        VendorBillConfirmed::dispatch($vendorBill);
    }
}
