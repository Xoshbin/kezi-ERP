<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\HR\Models\Employee;
use Kezi\HR\Services\HumanResources\EmployeeService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('creates audit log with reason when employee is terminated', function () {
    // Arrange
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'employment_status' => 'active',
    ]);

    $service = app(EmployeeService::class);
    $terminationDate = now()->toDateString();
    $reason = 'Performance issues';

    // Act
    $service->terminateEmployee($employee, $terminationDate, $reason, $this->user);

    // Assert
    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => Employee::class,
        'auditable_id' => $employee->id,
        'event_type' => 'employee_terminated',
        'description' => $reason,
        'company_id' => $this->company->id,
    ]);
});

it('creates audit log when employee is reactivated', function () {
    // Arrange
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'employment_status' => 'terminated',
        'is_active' => false,
        'termination_date' => now()->subDays(10)->toDateString(),
    ]);

    $service = app(EmployeeService::class);
    $reactivationDate = now()->toDateString();

    // Act
    $service->reactivateEmployee($employee, $reactivationDate, $this->user);

    // Assert
    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => Employee::class,
        'auditable_id' => $employee->id,
        'event_type' => 'employee_reactivated',
        'company_id' => $this->company->id,
    ]);
});

it('creates audit log when employee is transferred', function () {
    // Arrange
    $oldDepartment = \Kezi\HR\Models\Department::factory()->create(['company_id' => $this->company->id]);
    $newDepartment = \Kezi\HR\Models\Department::factory()->create(['company_id' => $this->company->id]);

    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'department_id' => $oldDepartment->id,
        'position_id' => null,
        'manager_id' => null,
        'employment_status' => 'active',
    ]);

    $service = app(EmployeeService::class);
    $effectiveDate = now()->toDateString();

    // Act
    $service->transferEmployee($employee, $newDepartment->id, null, null, $effectiveDate, $this->user);

    // Assert
    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => Employee::class,
        'auditable_id' => $employee->id,
        'event_type' => 'employee_transferred',
        'company_id' => $this->company->id,
    ]);
});
