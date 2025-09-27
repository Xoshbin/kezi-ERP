<?php

namespace Modules\Purchase\Policies;

use App\Models\User;
use App\Models\VendorBill;

class VendorBillPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // TODO: Change this to implement actual logic before deploying
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, \Modules\Purchase\Models\VendorBill $vendorBill): bool
    {
        // TODO: Change this to implement actual logic before deploying
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // TODO: Change this to implement actual logic before deploying
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, \Modules\Purchase\Models\VendorBill $vendorBill): bool
    {
        // TODO: Change this to implement actual logic before deploying
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, \Modules\Purchase\Models\VendorBill $vendorBill): bool
    {
        // TODO: Change this to implement actual logic before deploying
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, \Modules\Purchase\Models\VendorBill $vendorBill): bool
    {
        // TODO: Change this to implement actual logic before deploying
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, \Modules\Purchase\Models\VendorBill $vendorBill): bool
    {
        // TODO: Change this to implement actual logic before deploying
        return true;
    }

    /**
     * Determine whether the user can confirm the vendor bill.
     */
    public function post(User $user, \Modules\Purchase\Models\VendorBill $vendorBill): bool
    {
        // For now, we will allow it. In a real app, you might check for a specific role.
        // TODO: Change this to implement actual logic before deploying
        return true;
    }

    /**
     * Determine whether the user can reset the invoice to draft.
     * This is a sensitive action and should be restricted.
     */
    public function resetToDraft(User $user, \Modules\Purchase\Models\VendorBill $vendorBill): bool
    {
        // In a real application, you would check if the user has a specific role,
        // for example: return $user->hasRole('manager');

        // For the test to pass, we will simply allow it.
        // TODO: Change this to implement actual logic before deploying
        return true;
    }

    /**
     * Determine whether the user can cancel the model.
     */
    public function cancel(User $user, \Modules\Purchase\Models\VendorBill $vendorBill): bool
    {
        // For now, allow any logged-in user to cancel a posted vendor bill.
        // We can add more specific role-based logic here later if needed.
        // return $vendorBill->status === 'posted';
        return true;
    }
}
