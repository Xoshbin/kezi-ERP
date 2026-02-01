<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\HR\Actions\HumanResources\CreateEmployeeAction;
use Kezi\HR\DataTransferObjects\HumanResources\CreateEmployeeDTO;
use Kezi\HR\Models\Department;
use Kezi\HR\Models\Employee;
use Kezi\HR\Models\Position;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

describe('CreateEmployeeAction', function () {
    it('creates an employee with basic details', function () {
        // Arrange
        $department = Department::factory()->create(['company_id' => $this->company->id]);
        $position = Position::factory()->create(['company_id' => $this->company->id, 'department_id' => $department->id]);

        $dto = new CreateEmployeeDTO(
            company_id: $this->company->id,
            user_id: null,
            department_id: $department->id,
            position_id: $position->id,
            manager_id: null,
            employee_number: '', // Should be auto-generated
            first_name: 'John',
            last_name: 'Doe',
            email: 'john.doe@example.com',
            phone: '1234567890',
            date_of_birth: '1990-01-01',
            gender: 'Male',
            marital_status: 'Single',
            nationality: 'US',
            national_id: '123456789',
            passport_number: 'A1234567',
            address_line_1: '123 Main St',
            address_line_2: null,
            city: 'New York',
            state: 'NY',
            zip_code: '10001',
            country: 'USA',
            emergency_contact_name: 'Jane Doe',
            emergency_contact_phone: '0987654321',
            emergency_contact_relationship: 'Spouse',
            hire_date: '2023-01-01',
            termination_date: null,
            employment_status: 'Active',
            employee_type: 'Full-time',
            bank_name: 'Bank of America',
            bank_account_number: '123456789',
            bank_routing_number: '987654321',
            is_active: true,
            created_by_user_id: $this->user->id,
        );

        // Act
        $action = app(CreateEmployeeAction::class);
        $employee = $action->execute($dto);

        // Assert
        expect($employee)->toBeInstanceOf(Employee::class);
        expect($employee->company_id)->toBe($this->company->id);
        expect($employee->first_name)->toBe('John');
        expect($employee->last_name)->toBe('Doe');
        expect($employee->email)->toBe('john.doe@example.com');
        expect($employee->employee_number)->not->toBeEmpty();
        expect($employee->department_id)->toBe($department->id);
        expect($employee->position_id)->toBe($position->id);
        // Note: property is employment_status in DTO but might be just status in Model?
        // Checking Employee model, status might be stored in 'employment_status' column or 'status'.
        // Looking at CreateEmployeeAction, it maps 'employment_status' => $dto->employment_status
        expect($employee->employment_status)->toBe('Active');
    });

    it('generates unique employee number if not provided', function () {
        $dto1 = new CreateEmployeeDTO(
            company_id: $this->company->id,
            user_id: null,
            department_id: null,
            position_id: null,
            manager_id: null,
            employee_number: '',
            first_name: 'John',
            last_name: 'Doe',
            email: 'john.doe1@example.com',
            phone: '1234567890',
            date_of_birth: '1990-01-01',
            gender: 'Male',
            marital_status: 'Single',
            nationality: 'US',
            national_id: '123456789',
            passport_number: 'A1234567',
            address_line_1: '123 Main St',
            address_line_2: null,
            city: 'New York',
            state: 'NY',
            zip_code: '10001',
            country: 'USA',
            emergency_contact_name: 'Jane Doe',
            emergency_contact_phone: '0987654321',
            emergency_contact_relationship: 'Spouse',
            hire_date: '2023-01-01',
            termination_date: null,
            employment_status: 'Active',
            employee_type: 'Full-time',
            bank_name: 'Bank of America',
            bank_account_number: '123456789',
            bank_routing_number: '987654321',
            is_active: true,
            created_by_user_id: $this->user->id,
        );

        $dto2 = new CreateEmployeeDTO(
            company_id: $this->company->id,
            user_id: null,
            department_id: null,
            position_id: null,
            manager_id: null,
            employee_number: '',
            first_name: 'Jane',
            last_name: 'Smith',
            email: 'jane.smith@example.com',
            phone: '0987654321',
            date_of_birth: '1992-05-15',
            gender: 'Female',
            marital_status: 'Single',
            nationality: 'US',
            national_id: '987654321',
            passport_number: 'B1234567',
            address_line_1: '456 Elm St',
            address_line_2: null,
            city: 'San Francisco',
            state: 'CA',
            zip_code: '94101',
            country: 'USA',
            emergency_contact_name: 'John Doe',
            emergency_contact_phone: '1234567890',
            emergency_contact_relationship: 'Parent',
            hire_date: '2023-02-01',
            termination_date: null,
            employment_status: 'Active',
            employee_type: 'Part-time',
            bank_name: 'Chase',
            bank_account_number: '987654321',
            bank_routing_number: '123456789',
            is_active: true,
            created_by_user_id: $this->user->id,
        );

        $action = app(CreateEmployeeAction::class);
        $emp1 = $action->execute($dto1);
        $emp2 = $action->execute($dto2);

        expect($emp1->employee_number)->not->toBeEmpty();
        expect($emp2->employee_number)->not->toBeEmpty();
        expect($emp1->employee_number)->not->toBe($emp2->employee_number);
    });

    it('can set employee number manually', function () {
        $dto = new CreateEmployeeDTO(
            company_id: $this->company->id,
            user_id: null,
            department_id: null,
            position_id: null,
            manager_id: null,
            employee_number: 'EMP-MANUAL-001',
            first_name: 'Manual',
            last_name: 'Employee',
            email: 'manual.emp@example.com',
            phone: '5555555555',
            date_of_birth: '1985-08-20',
            gender: 'Male',
            marital_status: 'Married',
            nationality: 'US',
            national_id: '555555555',
            passport_number: 'M1234567',
            address_line_1: '789 Oak St',
            address_line_2: null,
            city: 'Chicago',
            state: 'IL',
            zip_code: '60601',
            country: 'USA',
            emergency_contact_name: 'Wife',
            emergency_contact_phone: '5555555555',
            emergency_contact_relationship: 'Spouse',
            hire_date: '2023-03-01',
            termination_date: null,
            employment_status: 'Active',
            employee_type: 'Full-time',
            bank_name: 'Wells Fargo',
            bank_account_number: '555555555',
            bank_routing_number: '555555555',
            is_active: true,
            created_by_user_id: $this->user->id,
        );

        $action = app(CreateEmployeeAction::class);
        $employee = $action->execute($dto);

        expect($employee->employee_number)->toBe('EMP-MANUAL-001');
    });
});
