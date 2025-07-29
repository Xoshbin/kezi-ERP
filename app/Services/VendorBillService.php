<?php

namespace App\Services;

use App\Actions\Accounting\CreateJournalEntryForVendorBillAction; // 1. Add the import for the new action.
use App\Events\VendorBillConfirmed;
use App\Exceptions\DeletionNotAllowedException;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\User;
use App\Models\VendorBill;
use App\Models\JournalEntry;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VendorBillService
{
    public function __construct(
        // The JournalEntryService is no longer needed here.
        protected AccountingValidationService $accountingValidationService
    ) {
    }

    /**
     * Create a new draft vendor bill.
     */
    public function create(array $data): VendorBill
    {
        // First, check if the entry's date is in a locked period.
        $this->accountingValidationService->checkIfPeriodIsLocked($data['company_id'], $data['bill_date']);

        return DB::transaction(function () use ($data) {
            $vendorBill = new VendorBill();
            $vendorBillData = collect($data)->except('lines')->all();
            if (isset($vendorBillData['partner_id'])) {
                $vendorBillData['vendor_id'] = $vendorBillData['partner_id'];
                unset($vendorBillData['partner_id']);
            }
            $vendorBill->fill($vendorBillData);
            $vendorBill->status = VendorBill::TYPE_DRAFT;

            // MODIFIED: Initialize totals with Money objects
            $currencyCode = $vendorBill->currency->code;
            $vendorBill->total_amount = Money::of(0, $currencyCode);
            $vendorBill->total_tax = Money::of(0, $currencyCode);
            $vendorBill->save(); // Save the vendor bill first to get an ID

            if (isset($data['lines'])) {
                // MODIFIED: createMany can have issues with casts, create one-by-one for safety
                foreach ($data['lines'] as $lineData) {
                    // MODIFIED: Manually create the Money object before passing it to the model layer.
                    // This ensures the MoneyCast receives an object and doesn't need to resolve the currency.
                    $lineData['unit_price'] = Money::of($lineData['unit_price'], $currencyCode);
                    $vendorBill->lines()->create($lineData);
                }
            }

            $vendorBill->save();

            return $vendorBill;
        });
    }

    /**
     * Confirm a draft vendor bill, post it, and create the corresponding journal entry.
     */
    public function confirm(VendorBill $vendorBill, User $user): void
    {
        if ($vendorBill->status !== VendorBill::TYPE_DRAFT) {
            return; // Or throw an exception
        }

        // First, check if the entry's date is in a locked period.
        $this->accountingValidationService->checkIfPeriodIsLocked($vendorBill->company_id, $vendorBill->bill_date);

        Gate::forUser($user)->authorize('confirm', $vendorBill);

        DB::transaction(function () use ($vendorBill, $user) {
            $this->_confirmAndPostBill($vendorBill, $user);
        });
    }

    /**
     * Update a draft vendor bill. Can also handle posting in the same action.
     */
    public function update(VendorBill $vendorBill, array $data, User $user): VendorBill
    {
        // First, check if the entry's date is in a locked period.
        $this->accountingValidationService->checkIfPeriodIsLocked($vendorBill->company_id, $data['bill_date'] ?? $vendorBill->bill_date);

        if ($vendorBill->status !== VendorBill::TYPE_DRAFT) {
            throw new UpdateNotAllowedException('Cannot update a posted vendor bill.');
        }

        $isPosting = isset($data['status']) && $data['status'] === VendorBill::TYPE_POSTED;

        DB::transaction(function () use ($vendorBill, $data, $user, $isPosting) {
            $updateData = collect($data)->except('status')->all();

            if (isset($updateData['partner_id'])) {
                $updateData['vendor_id'] = $updateData['partner_id'];
                unset($updateData['partner_id']);
            }

            if (isset($updateData['lines'])) {
                $vendorBill->lines()->delete();
                // MODIFIED: createMany can have issues with casts, create one-by-one for safety
                foreach ($updateData['lines'] as $lineData) {
                    $vendorBill->lines()->create($lineData);
                }
                unset($updateData['lines']);
            }

            $vendorBill->fill($updateData);

            if ($isPosting) {
                $this->_confirmAndPostBill($vendorBill, $user);
            } else {
                $vendorBill->save();
            }
        });

        return $vendorBill;
    }

    /**
     * Delete a draft vendor bill.
     */
    public function delete(VendorBill $vendorBill): bool
    {

        // First, check if the entry's date is in a locked period.
        // This applies to ALL entries, whether draft or posted, if their date falls within a locked period.
        $this->accountingValidationService->checkIfPeriodIsLocked($vendorBill->company_id, $vendorBill->bill_date);

        // Block deletion if the entry has been posted.
        // Block deletion if the entry has been posted. This is the non-negotiable immutability rule.
        if ($vendorBill->status !== VendorBill::TYPE_DRAFT) {
            throw new DeletionNotAllowedException(
                'Cannot delete a posted vendor bill. Corrections must be made with a new reversal entry.'
            );
        }

        // Proceed with deletion for draft entries.
        // Using a transaction is good practice, though Eloquent's delete handles this well.
        return DB::transaction(function () use ($vendorBill) {
            // Deleting the JournalEntry will also delete its lines if foreign keys
            // are configured with `onDelete('cascade')`.
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


    /**
     * Internal method to perform the actual posting logic. Assumes it's running inside a transaction.
     */
    private function _confirmAndPostBill(VendorBill $vendorBill, User $user): void
    {
        // 1. Update status and posting date.
        // The `saving` event on the VendorBill model will handle recalculating totals.
        $vendorBill->status = VendorBill::TYPE_POSTED;
        $vendorBill->posted_at = now();

        // 3. Create the accounting entry using our new, dedicated Action.
        $journalEntry = (new CreateJournalEntryForVendorBillAction())->execute($vendorBill, $user);
        $vendorBill->journal_entry_id = $journalEntry->id;

        // 4. Save all changes to the bill.
        $vendorBill->save();

        // 5. Dispatch event.
        VendorBillConfirmed::dispatch($vendorBill);
    }

    // 4. The old private method is now removed.
    // private function createJournalEntryForBill(...) has been deleted.
}
