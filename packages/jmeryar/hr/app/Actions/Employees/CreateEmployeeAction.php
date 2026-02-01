<?php

namespace Jmeryar\HR\Actions\Employees;

use Illuminate\Support\Facades\DB;
use Jmeryar\HR\DataTransferObjects\Employees\EmployeeDTO;
use Jmeryar\HR\Models\Employee;

class CreateEmployeeAction
{
    public function execute(EmployeeDTO $dto): Employee
    {
        return DB::transaction(function () use ($dto) {
            $employee = new Employee;

            $employee->company_id = $dto->company_id;
            $employee->user_id = $dto->user_id;
            $employee->department_id = $dto->department_id;
            $employee->position_id = $dto->position_id;
            $employee->manager_id = $dto->manager_id;

            // Business logic: Generate employee number if not provided
            $employee->employee_number = $dto->employee_number ?? Employee::generateEmployeeNumber($employee->company);

            $employee->first_name = $dto->first_name;
            $employee->last_name = $dto->last_name;
            $employee->email = $dto->email;
            $employee->phone = $dto->phone;
            $employee->date_of_birth = $dto->date_of_birth;
            $employee->gender = $dto->gender;
            $employee->marital_status = $dto->marital_status;
            $employee->nationality = $dto->nationality;
            $employee->national_id = $dto->national_id;
            $employee->passport_number = $dto->passport_number;
            $employee->address_line_1 = $dto->address_line_1;
            $employee->address_line_2 = $dto->address_line_2;
            $employee->city = $dto->city;
            $employee->state = $dto->state;
            $employee->zip_code = $dto->zip_code;
            $employee->country = $dto->country;
            $employee->emergency_contact_name = $dto->emergency_contact_name;
            $employee->emergency_contact_phone = $dto->emergency_contact_phone;
            $employee->emergency_contact_relationship = $dto->emergency_contact_relationship;
            $employee->hire_date = $dto->hire_date;
            $employee->termination_date = $dto->termination_date;
            $employee->employment_status = $dto->employment_status;
            $employee->employee_type = $dto->employee_type;
            $employee->bank_name = $dto->bank_name;
            $employee->bank_account_number = $dto->bank_account_number;
            $employee->bank_routing_number = $dto->bank_routing_number;
            $employee->is_active = $dto->is_active;

            $employee->save();

            // Handle custom fields if any (this would typically use the custom fields service/trait logic)
            if (! empty($dto->customFields)) {
                // Logic to save custom fields would go here
                // For now, we assume the model handles them if configured
            }

            return $employee;
        });
    }
}
