<?php

namespace Modules\HR\Policies;

use App\Models\User;
use Modules\HR\Models\Payroll;


class PayrollPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Payroll $payroll): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Payroll $payroll): bool
    {
        // Only allow updates to draft payrolls
        return $payroll->status === 'draft';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Payroll $payroll): bool
    {
        // Only allow deletion of draft payrolls
        return $payroll->status === 'draft';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Payroll $payroll): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Payroll $payroll): bool
    {
        return true;
    }

    /**
     * Determine whether the user can approve the payroll.
     */
    public function approve(User $user, Payroll $payroll): bool
    {
        // Only allow approval of draft payrolls
        return $payroll->status === 'draft';
    }

    /**
     * Determine whether the user can pay the employee.
     */
    public function pay(User $user, Payroll $payroll): bool
    {
        // Allow the action - business logic will handle validation
        return true;
    }
}
