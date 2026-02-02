<?php

namespace Kezi\HR\DataTransferObjects\Employees;

use Illuminate\Support\Carbon;

readonly class EmployeeDTO
{
    /**
     * @param  array<string, mixed>  $customFields
     */
    public function __construct(
        public string $first_name,
        public string $last_name,
        public string $email,
        public Carbon $hire_date,
        public string $employment_status,
        public string $employee_type,
        public bool $is_active,
        public ?int $company_id = null,
        public ?int $user_id = null,
        public ?int $department_id = null,
        public ?int $position_id = null,
        public ?int $manager_id = null,
        public ?string $employee_number = null,
        public ?string $phone = null,
        public ?Carbon $date_of_birth = null,
        public ?string $gender = null,
        public ?string $marital_status = null,
        public ?string $nationality = null,
        public ?string $national_id = null,
        public ?string $passport_number = null,
        public ?string $address_line_1 = null,
        public ?string $address_line_2 = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $zip_code = null,
        public ?string $country = null,
        public ?string $emergency_contact_name = null,
        public ?string $emergency_contact_phone = null,
        public ?string $emergency_contact_relationship = null,
        public ?Carbon $termination_date = null,
        public ?string $bank_name = null,
        public ?string $bank_account_number = null,
        public ?string $bank_routing_number = null,
        public array $customFields = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            first_name: $data['first_name'],
            last_name: $data['last_name'],
            email: $data['email'],
            hire_date: $data['hire_date'] instanceof Carbon ? $data['hire_date'] : Carbon::parse($data['hire_date']),
            employment_status: $data['employment_status'],
            employee_type: $data['employee_type'],
            is_active: (bool) ($data['is_active'] ?? true),
            company_id: $data['company_id'] ?? null,
            user_id: $data['user_id'] ?? null,
            department_id: $data['department_id'] ?? null,
            position_id: $data['position_id'] ?? null,
            manager_id: $data['manager_id'] ?? null,
            employee_number: $data['employee_number'] ?? null,
            phone: $data['phone'] ?? null,
            date_of_birth: isset($data['date_of_birth']) ? ($data['date_of_birth'] instanceof Carbon ? $data['date_of_birth'] : Carbon::parse($data['date_of_birth'])) : null,
            gender: $data['gender'] ?? null,
            marital_status: $data['marital_status'] ?? null,
            nationality: $data['nationality'] ?? null,
            national_id: $data['national_id'] ?? null,
            passport_number: $data['passport_number'] ?? null,
            address_line_1: $data['address_line_1'] ?? null,
            address_line_2: $data['address_line_2'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            zip_code: $data['zip_code'] ?? null,
            country: $data['country'] ?? null,
            emergency_contact_name: $data['emergency_contact_name'] ?? null,
            emergency_contact_phone: $data['emergency_contact_phone'] ?? null,
            emergency_contact_relationship: $data['emergency_contact_relationship'] ?? null,
            termination_date: isset($data['termination_date']) ? ($data['termination_date'] instanceof Carbon ? $data['termination_date'] : Carbon::parse($data['termination_date'])) : null,
            bank_name: $data['bank_name'] ?? null,
            bank_account_number: $data['bank_account_number'] ?? null,
            bank_routing_number: $data['bank_routing_number'] ?? null,
            customFields: $data['customFields'] ?? [],
        );
    }
}
