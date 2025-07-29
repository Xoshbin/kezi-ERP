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
use App\Models\User;
use App\Models\VendorBill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VendorBillService
{
    public function __construct(
        protected AccountingValidationService $accountingValidationService
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
        if ($vendorBill->status !== VendorBill::TYPE_DRAFT) {
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

        if ($vendorBill->status !== VendorBill::TYPE_DRAFT) {
            throw new DeletionNotAllowedException(
                'Cannot delete a posted vendor bill. Corrections must be made with a new reversal entry.'
            );
        }

        return DB::transaction(function () use ($vendorBill) {
            return $vendorBill->delete();
        });
    }

    /**
     * Reset a posted vendor bill back to draft.
     */
    public function resetToDraft(VendorBill $vendorBill, User $user, string $reason): void
    {
        Gate::forUser($user)->authorize('resetToDraft', $vendorBill);

        DB::transaction(function () use ($vendorBill, $user, $reason) {
            $vendorBill->journalEntry()->delete();

            $newLog = [
                'user_id' => $user->id,
                'timestamp' => now()->toDateTimeString(),
                'reason' => $reason,
            ];
            $logs = $vendorBill->reset_to_draft_log ?: [];
            array_unshift($logs, $newLog);

            $vendorBill->status = VendorBill::TYPE_DRAFT;
            $vendorBill->journal_entry_id = null;
            $vendorBill->posted_at = null;
            $vendorBill->reset_to_draft_log = $logs;

            $vendorBill->save();
        });
    }

    private function _confirmAndPostBill(VendorBill $vendorBill, User $user): void
    {
        $vendorBill->status = VendorBill::TYPE_POSTED;
        $vendorBill->posted_at = now();

        $journalEntry = (new CreateJournalEntryForVendorBillAction())->execute($vendorBill, $user);
        $vendorBill->journal_entry_id = $journalEntry->id;

        $vendorBill->save();

        VendorBillConfirmed::dispatch($vendorBill);
    }
}
