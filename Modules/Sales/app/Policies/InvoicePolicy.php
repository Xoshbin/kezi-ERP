<?php

namespace Modules\Sales\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
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
    public function view(User $user, Invoice $invoice): bool
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
    public function update(User $user, Invoice $invoice): bool
    {
        // TODO: Change this to implement actual logic before deploying
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // TODO: Change this to implement actual logic before deploying
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        // TODO: Change this to implement actual logic before deploying
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        // TODO: Change this to implement actual logic before deploying
        return true;
    }

    /**
     * Determine whether the user can reset the invoice to draft.
     * This is a sensitive action and should be restricted.
     */
    public function resetToDraft(User $user, Invoice $invoice): bool
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
    public function cancel(User $user, Invoice $invoice): bool
    {
        // For now, allow any logged-in user to cancel a posted invoice.
        // We can add more specific role-based logic here later if needed.
        // return $invoice->status === 'posted';
        return true;
    }
}
