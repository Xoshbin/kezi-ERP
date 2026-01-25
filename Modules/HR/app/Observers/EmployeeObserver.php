<?php

namespace Modules\HR\Observers;

use App\Models\Company;
use Filament\Facades\Filament;
use Modules\HR\Models\Employee;

class EmployeeObserver
{
    /**
     * Handle the Employee "creating" event.
     */
    public function creating(Employee $employee): void
    {
        /** @var Company|null $tenant */
        $tenant = Filament::getTenant();

        if (empty($employee->company_id) && $tenant) {
            $employee->company_id = $tenant->id;
        }

        if (empty($employee->employee_number) && $tenant) {
            $employee->employee_number = Employee::generateEmployeeNumber($tenant);
        }
    }
}
