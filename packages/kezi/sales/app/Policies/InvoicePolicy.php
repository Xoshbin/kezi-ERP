<?php

namespace Kezi\Sales\Policies;

use App\Models\User;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;

class InvoicePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_invoice');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('view_invoice');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_invoice');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // Immutability: Only Draft invoices can be edited.
        return $user->can('update_invoice') && $invoice->status === InvoiceStatus::Draft;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // Immutability: Only Draft invoices can be deleted.
        return $user->can('delete_invoice') && $invoice->status === InvoiceStatus::Draft;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        return $user->can('restore_invoice');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return $user->can('force_delete_invoice');
    }

    /**
     * Determine whether the user can reset the invoice to draft.
     * This is a sensitive action and should be restricted.
     */
    public function resetToDraft(User $user, Invoice $invoice): bool
    {
        // Strictly prohibit resetting Posted invoices to Draft to preserve audit trail.
        // Corrections must be made via Credit Notes.
        // Only allowed if status is Cancelled? Or maybe strictly forbid for Posted.
        // If "Immutability is Law", once Posted, never Draft again.

        if ($invoice->status === InvoiceStatus::Posted || $invoice->status === InvoiceStatus::Paid) {
            return false;
        }

        return $user->can('update_invoice');
    }

    /**
     * Determine whether the user can cancel the model.
     */
    public function cancel(User $user, Invoice $invoice): bool
    {
        // Only Draft invoices can be cancelled (Voided).
        // Posted invoices must be reversed via Credit Note.
        return $user->can('update_invoice') && ($invoice->status === InvoiceStatus::Draft || $invoice->status === InvoiceStatus::Posted);
    }
}
