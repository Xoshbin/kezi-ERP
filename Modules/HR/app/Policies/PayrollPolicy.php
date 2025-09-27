<?php

namespace Modules\HR\Policies;

use App\Models\Payroll;
use App\Models\User;

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
    public function view(User $user, \Modules\HR\Models\Payroll $payroll): bool
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
    public function update(User $user, \Modules\HR\Models\Payroll $payroll): bool
    {
        // Only allow updates to draft payrolls
        return $payroll->status === 'draft';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, \Modules\HR\Models\Payroll $payroll): bool
    {
        // Only allow deletion of draft payrolls
        return $payroll->status === 'draft';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, \Modules\HR\Models\Payroll $payroll): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, \Modules\HR\Models\Payroll $payroll): bool
    {
        return true;
    }

    /**
     * Determine whether the user can approve the payroll.
     */
    public function approve(User $user, \Modules\HR\Models\Payroll $payroll): bool
    {
        // Only allow approval of draft payrolls
        return $payroll->status === 'draft';
    }

    /**
     * Determine whether the user can pay the employee.
     */
    public function pay(User $user, \Modules\HR\Models\Payroll $payroll): bool
    {
        // Allow the action - business logic will handle validation
        return true;
    }
}
