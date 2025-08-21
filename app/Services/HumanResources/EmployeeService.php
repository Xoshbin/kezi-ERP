<?php

namespace App\Services\HumanResources;

use App\Actions\HumanResources\CreateEmployeeAction;
use App\Actions\HumanResources\CreateEmploymentContractAction;
use App\DataTransferObjects\HumanResources\CreateEmployeeDTO;
use App\DataTransferObjects\HumanResources\CreateEmploymentContractDTO;
use App\Models\Employee;
use App\Models\EmploymentContract;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class EmployeeService
{
    public function __construct(
        protected CreateEmployeeAction $createEmployeeAction,
        protected CreateEmploymentContractAction $createEmploymentContractAction,
    ) {
    }

    /**
     * Create a new employee with optional employment contract.
     */
    public function createEmployee(CreateEmployeeDTO $createEmployeeDTO, ?CreateEmploymentContractDTO $contractDTO = null): Employee
    {
        return DB::transaction(function () use ($createEmployeeDTO, $contractDTO) {
            // Create the employee
            $employee = $this->createEmployeeAction->execute($createEmployeeDTO);

            // Create employment contract if provided
            if ($contractDTO) {
                // Update the contract DTO with the employee ID
                $contractDTOWithEmployee = new CreateEmploymentContractDTO(
                    company_id: $contractDTO->company_id,
                    employee_id: $employee->id,
                    currency_id: $contractDTO->currency_id,
                    contract_number: $contractDTO->contract_number,
                    contract_type: $contractDTO->contract_type,
                    start_date: $contractDTO->start_date,
                    end_date: $contractDTO->end_date,
                    is_active: $contractDTO->is_active,
                    base_salary: $contractDTO->base_salary,
                    hourly_rate: $contractDTO->hourly_rate,
                    pay_frequency: $contractDTO->pay_frequency,
                    housing_allowance: $contractDTO->housing_allowance,
                    transport_allowance: $contractDTO->transport_allowance,
                    meal_allowance: $contractDTO->meal_allowance,
                    other_allowances: $contractDTO->other_allowances,
                    working_hours_per_week: $contractDTO->working_hours_per_week,
                    working_days_per_week: $contractDTO->working_days_per_week,
                    annual_leave_days: $contractDTO->annual_leave_days,
                    sick_leave_days: $contractDTO->sick_leave_days,
                    maternity_leave_days: $contractDTO->maternity_leave_days,
                    paternity_leave_days: $contractDTO->paternity_leave_days,
                    probation_period_months: $contractDTO->probation_period_months,
                    probation_end_date: $contractDTO->probation_end_date,
                    notice_period_days: $contractDTO->notice_period_days,
                    terms_and_conditions: $contractDTO->terms_and_conditions,
                    job_description: $contractDTO->job_description,
                    created_by_user_id: $contractDTO->created_by_user_id,
                );

                $this->createEmploymentContractAction->execute($contractDTOWithEmployee);
            }

            return $employee->fresh(['currentContract', 'department', 'position', 'manager']);
        });
    }

    /**
     * Terminate an employee.
     */
    public function terminateEmployee(Employee $employee, string $terminationDate, string $reason, User $user): void
    {
        Gate::forUser($user)->authorize('update', $employee);

        DB::transaction(function () use ($employee, $terminationDate, $reason) {
            // Update employee status
            $employee->update([
                'employment_status' => 'terminated',
                'termination_date' => $terminationDate,
                'is_active' => false,
            ]);

            // Deactivate current contract
            $currentContract = $employee->currentContract;
            if ($currentContract) {
                $currentContract->update([
                    'is_active' => false,
                    'end_date' => $terminationDate,
                ]);
            }

            // TODO: Create audit log entry for termination
            // TODO: Handle final payroll processing
            // TODO: Handle asset returns
        });
    }

    /**
     * Reactivate a terminated employee.
     */
    public function reactivateEmployee(Employee $employee, string $reactivationDate, User $user): void
    {
        Gate::forUser($user)->authorize('update', $employee);

        if ($employee->employment_status !== 'terminated') {
            throw new \Exception('Only terminated employees can be reactivated.');
        }

        DB::transaction(function () use ($employee, $reactivationDate) {
            $employee->update([
                'employment_status' => 'active',
                'termination_date' => null,
                'is_active' => true,
            ]);

            // TODO: Create new employment contract if needed
            // TODO: Create audit log entry for reactivation
        });
    }

    /**
     * Transfer employee to different department/position.
     */
    public function transferEmployee(Employee $employee, ?int $newDepartmentId, ?int $newPositionId, ?int $newManagerId, string $effectiveDate, User $user): void
    {
        Gate::forUser($user)->authorize('update', $employee);

        DB::transaction(function () use ($employee, $newDepartmentId, $newPositionId, $newManagerId, $effectiveDate) {
            $employee->update([
                'department_id' => $newDepartmentId ?? $employee->department_id,
                'position_id' => $newPositionId ?? $employee->position_id,
                'manager_id' => $newManagerId ?? $employee->manager_id,
            ]);

            // TODO: Create audit log entry for transfer
            // TODO: Handle contract updates if needed
        });
    }

    /**
     * Get employee statistics for a company.
     */
    public function getEmployeeStatistics(Company $company): array
    {
        $employees = Employee::where('company_id', $company->id);

        return [
            'total_employees' => $employees->count(),
            'active_employees' => $employees->where('employment_status', 'active')->count(),
            'terminated_employees' => $employees->where('employment_status', 'terminated')->count(),
            'on_probation' => $employees->whereHas('currentContract', function ($query) {
                $query->where('probation_end_date', '>', now());
            })->count(),
            'by_department' => $employees->with('department')
                ->get()
                ->groupBy('department.name')
                ->map(fn($group) => $group->count())
                ->toArray(),
            'by_employee_type' => $employees->get()
                ->groupBy('employee_type')
                ->map(fn($group) => $group->count())
                ->toArray(),
        ];
    }
}
