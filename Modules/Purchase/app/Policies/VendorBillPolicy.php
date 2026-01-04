<?php

namespace Modules\Purchase\Policies;

use App\Models\User;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;

class VendorBillPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_vendor_bill');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, VendorBill $vendorBill): bool
    {
        return $user->can('view_vendor_bill');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_vendor_bill');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VendorBill $vendorBill): bool
    {
        // Immutability: Only Draft bills can be edited.
        return $user->can('update_vendor_bill') && $vendorBill->isDraft();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VendorBill $vendorBill): bool
    {
        // Immutability: Only Draft bills can be deleted.
        return $user->can('delete_vendor_bill') && $vendorBill->isDraft();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, VendorBill $vendorBill): bool
    {
        return $user->can('restore_vendor_bill');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, VendorBill $vendorBill): bool
    {
        return $user->can('force_delete_vendor_bill');
    }

    /**
     * Determine whether the user can confirm the vendor bill.
     */
    public function post(User $user, VendorBill $vendorBill): bool
    {
        return $user->can('confirm_vendor_bill') && $vendorBill->isDraft();
    }

    /**
     * Determine whether the user can reset the invoice to draft.
     * This is a sensitive action and should be restricted.
     */
    public function resetToDraft(User $user, VendorBill $vendorBill): bool
    {
        // Strictly prohibit resetting Posted bills.
        if ($vendorBill->status === VendorBillStatus::Posted || $vendorBill->status === VendorBillStatus::Paid) {
            return false;
        }

        return $user->can('update_vendor_bill');
    }

    /**
     * Determine whether the user can cancel the model.
     */
    public function cancel(User $user, VendorBill $vendorBill): bool
    {
        // Only Draft bills can be cancelled (Voided).
        // Posted bills must be reversed via Debit Note.
        return $user->can('update_vendor_bill') && $vendorBill->isDraft();
    }
}
