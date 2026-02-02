<?php

namespace Kezi\HR\DataTransferObjects\HumanResources;

readonly class CreateEmployeeDTO
{
    public function __construct(
        public int $company_id,
        public ?int $user_id,
        public ?int $department_id,
        public ?int $position_id,
        public ?int $manager_id,
        public string $employee_number,
        public string $first_name,
        public string $last_name,
        public string $email,
        public ?string $phone,
        public ?string $date_of_birth,
        public ?string $gender,
        public ?string $marital_status,
        public ?string $nationality,
        public ?string $national_id,
        public ?string $passport_number,
        public ?string $address_line_1,
        public ?string $address_line_2,
        public ?string $city,
        public ?string $state,
        public ?string $zip_code,
        public ?string $country,
        public ?string $emergency_contact_name,
        public ?string $emergency_contact_phone,
        public ?string $emergency_contact_relationship,
        public string $hire_date,
        public ?string $termination_date,
        public string $employment_status,
        public string $employee_type,
        public ?string $bank_name,
        public ?string $bank_account_number,
        public ?string $bank_routing_number,
        public bool $is_active,
        public int $created_by_user_id,
    ) {}
}
