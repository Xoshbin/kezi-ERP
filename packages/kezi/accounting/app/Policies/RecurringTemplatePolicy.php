<?php

namespace Kezi\Accounting\Policies;

use App\Models\User;
use Kezi\Accounting\Models\RecurringTemplate;

class RecurringTemplatePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('view_any_recurring_template');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RecurringTemplate $recurringTemplate): bool
    {
        return $user->hasRole('super_admin') || $user->can('view_recurring_template');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('create_recurring_template');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RecurringTemplate $recurringTemplate): bool
    {
        return $user->hasRole('super_admin') || $user->can('update_recurring_template');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RecurringTemplate $recurringTemplate): bool
    {
        return $user->hasRole('super_admin') || $user->can('delete_recurring_template');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RecurringTemplate $recurringTemplate): bool
    {
        return $user->hasRole('super_admin') || $user->can('restore_recurring_template');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RecurringTemplate $recurringTemplate): bool
    {
        return $user->hasRole('super_admin') || $user->can('force_delete_recurring_template');
    }
}
