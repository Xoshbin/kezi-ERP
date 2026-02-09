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
