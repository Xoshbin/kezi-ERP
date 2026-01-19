<?php

use Illuminate\Support\Carbon;
use Modules\HR\Actions\HumanResources\CreateLeaveRequestAction;
use Modules\HR\DataTransferObjects\HumanResources\CreateLeaveRequestDTO;
use Modules\HR\Models\Employee;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\LeaveType;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

test('it creates leave request successfully', function () {
    // Arrange
    $employee = Employee::factory()->create(['company_id' => $this->company->id]);
    $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
    $startDate = Carbon::today()->addDays(5);
    $endDate = Carbon::today()->addDays(7);

    $dto = new CreateLeaveRequestDTO(
        company_id: $this->company->id,
        employee_id: $employee->id,
        leave_type_id: $leaveType->id,
        start_date: $startDate->format('Y-m-d'),
        end_date: $endDate->format('Y-m-d'),
        days_requested: 3,
        reason: 'Vacation',
        requested_by_user_id: $this->user->id,
        notes: 'Family trip',
        delegate_employee_id: null,
        delegation_notes: null,
        attachments: null,
        request_number: null,
    );

    // Act
    $action = app(CreateLeaveRequestAction::class);
    $leaveRequest = $action->execute($dto);

    // Assert
    expect($leaveRequest)->toBeInstanceOf(LeaveRequest::class)
        ->and($leaveRequest->company_id)->toBe($this->company->id)
        ->and($leaveRequest->employee_id)->toBe($employee->id)
        ->and($leaveRequest->leave_type_id)->toBe($leaveType->id)
        ->and($leaveRequest->start_date->format('Y-m-d'))->toBe($startDate->format('Y-m-d'))
        ->and($leaveRequest->end_date->format('Y-m-d'))->toBe($endDate->format('Y-m-d'))
        ->and($leaveRequest->days_requested)->toBe('3.00')
        ->and($leaveRequest->reason)->toBe('Vacation')
        ->and($leaveRequest->status)->toBe('pending');
});

test('it generates request number when not provided', function () {
    // Arrange
    $employee = Employee::factory()->create(['company_id' => $this->company->id]);
    $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
    $startDate = Carbon::today()->addDays(5);
    $endDate = Carbon::today()->addDays(7);

    $dto = new CreateLeaveRequestDTO(
        company_id: $this->company->id,
        employee_id: $employee->id,
        leave_type_id: $leaveType->id,
        start_date: $startDate->format('Y-m-d'),
        end_date: $endDate->format('Y-m-d'),
        days_requested: 3,
        reason: 'Vacation',
        requested_by_user_id: $this->user->id,
    );

    // Act
    $action = app(CreateLeaveRequestAction::class);
    $leaveRequest = $action->execute($dto);

    // Assert
    expect($leaveRequest->request_number)->not->toBeNull()
        ->and($leaveRequest->request_number)->toMatch('/^LR\d{4}\d{4}$/');
});

test('it can set delegate employee', function () {
    // Arrange
    $employee = Employee::factory()->create(['company_id' => $this->company->id]);
    $delegate = Employee::factory()->create(['company_id' => $this->company->id]);
    $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);

    $dto = new CreateLeaveRequestDTO(
        company_id: $this->company->id,
        employee_id: $employee->id,
        leave_type_id: $leaveType->id,
        start_date: Carbon::today()->format('Y-m-d'),
        end_date: Carbon::today()->addDay()->format('Y-m-d'),
        days_requested: 2,
        reason: 'Sick',
        requested_by_user_id: $this->user->id,
        delegate_employee_id: $delegate->id,
        delegation_notes: 'Please contact him',
    );

    // Act
    $leaveRequest = app(CreateLeaveRequestAction::class)->execute($dto);

    // Assert
    expect($leaveRequest->delegate_employee_id)->toBe($delegate->id)
        ->and($leaveRequest->delegation_notes)->toBe('Please contact him');
});
