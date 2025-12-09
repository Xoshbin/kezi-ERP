<?php

namespace Modules\HR\Actions\HumanResources;

use RuntimeException;
use InvalidArgumentException;
use App\Models\Company;
use Modules\HR\Models\Employee;
use Illuminate\Support\Facades\DB;
use Modules\HR\DataTransferObjects\HumanResources\CreateEmployeeDTO;

class CreateEmployeeAction
{
    public function execute(CreateEmployeeDTO $createEmployeeDTO): Employee
    {
        return DB::transaction(function () use ($createEmployeeDTO): Employee {
            // Generate employee number if not provided
            $employeeNumber = $createEmployeeDTO->employee_number;
            if (empty($employeeNumber)) {
                $company = Company::find($createEmployeeDTO->company_id);
                if (! $company) {
                    throw new InvalidArgumentException('Company not found');
                }
                $employeeNumber = Employee::generateEmployeeNumber($company);
            }

            $employee = Employee::create([
                'company_id' => $createEmployeeDTO->company_id,
                'user_id' => $createEmployeeDTO->user_id,
                'department_id' => $createEmployeeDTO->department_id,
                'position_id' => $createEmployeeDTO->position_id,
                'manager_id' => $createEmployeeDTO->manager_id,
                'employee_number' => $employeeNumber,
                'first_name' => $createEmployeeDTO->first_name,
                'last_name' => $createEmployeeDTO->last_name,
                'email' => $createEmployeeDTO->email,
                'phone' => $createEmployeeDTO->phone,
                'date_of_birth' => $createEmployeeDTO->date_of_birth,
                'gender' => $createEmployeeDTO->gender,
                'marital_status' => $createEmployeeDTO->marital_status,
                'nationality' => $createEmployeeDTO->nationality,
                'national_id' => $createEmployeeDTO->national_id,
                'passport_number' => $createEmployeeDTO->passport_number,
                'address_line_1' => $createEmployeeDTO->address_line_1,
                'address_line_2' => $createEmployeeDTO->address_line_2,
                'city' => $createEmployeeDTO->city,
                'state' => $createEmployeeDTO->state,
                'zip_code' => $createEmployeeDTO->zip_code,
                'country' => $createEmployeeDTO->country,
                'emergency_contact_name' => $createEmployeeDTO->emergency_contact_name,
                'emergency_contact_phone' => $createEmployeeDTO->emergency_contact_phone,
                'emergency_contact_relationship' => $createEmployeeDTO->emergency_contact_relationship,
                'hire_date' => $createEmployeeDTO->hire_date,
                'termination_date' => $createEmployeeDTO->termination_date,
                'employment_status' => $createEmployeeDTO->employment_status,
                'employee_type' => $createEmployeeDTO->employee_type,
                'bank_name' => $createEmployeeDTO->bank_name,
                'bank_account_number' => $createEmployeeDTO->bank_account_number,
                'bank_routing_number' => $createEmployeeDTO->bank_routing_number,
                'is_active' => $createEmployeeDTO->is_active,
            ]);

            $fresh = $employee->fresh();
            if (! $fresh) {
                throw new RuntimeException('Failed to refresh employee after creation');
            }

            return $fresh;
        });
    }
}
