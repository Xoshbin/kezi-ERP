<?php

namespace Kezi\ProjectManagement\Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\HR\Models\Employee;
use Kezi\ProjectManagement\DataTransferObjects\CreateTimesheetDTO;
use Kezi\ProjectManagement\DataTransferObjects\TimesheetLineDTO;
use Kezi\ProjectManagement\Enums\TimesheetStatus;
use Kezi\ProjectManagement\Models\Timesheet;
use Kezi\ProjectManagement\Services\TimesheetService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->service = app(TimesheetService::class);
});

it('creates a timesheet successfully', function () {
    $employee = Employee::factory()->create(['company_id' => $this->company->id]);

    $dto = new CreateTimesheetDTO(
        company_id: $this->company->id,
        employee_id: $employee->id,
        start_date: now()->startOfWeek(),
        end_date: now()->endOfWeek(),
        status: TimesheetStatus::Draft,
        lines: [] // Empty lines for creating header
    );

    $timesheet = $this->service->createTimesheet($dto);

    expect($timesheet)->toBeInstanceOf(Timesheet::class)
        ->and($timesheet->employee_id)->toBe($employee->id)
        ->and($timesheet->status)->toBe(TimesheetStatus::Draft);
});

it('submits a timesheet', function () {
    $timesheet = Timesheet::factory()->create([
        'company_id' => $this->company->id,
        'status' => TimesheetStatus::Draft,
    ]);

    \Kezi\ProjectManagement\Models\TimesheetLine::factory()->create([
        'timesheet_id' => $timesheet->id,
        'hours' => 8,
    ]);

    $this->service->submitTimesheet($timesheet);

    expect($timesheet->fresh()->status)->toBe(TimesheetStatus::Submitted);
});

it('approves a timesheet', function () {
    $timesheet = Timesheet::factory()->create([
        'company_id' => $this->company->id,
        'status' => TimesheetStatus::Submitted,
    ]);

    // User is the approver
    $user = \App\Models\User::factory()->create();

    $this->service->approveTimesheet($timesheet, $user);

    expect($timesheet->fresh()->status)->toBe(TimesheetStatus::Approved)
        ->and($timesheet->fresh()->approved_by)->toBe($user->id);
});

it('rejects a timesheet', function () {
    $timesheet = Timesheet::factory()->create([
        'company_id' => $this->company->id,
        'status' => TimesheetStatus::Submitted,
    ]);

    $user = \App\Models\User::factory()->create();

    $this->service->rejectTimesheet($timesheet, $user, 'Reason');

    expect($timesheet->fresh()->status)->toBe(TimesheetStatus::Rejected)
        ->and($timesheet->rejection_reason)->toBe('Reason');
});

it('validates timesheet lines', function () {
    // Valid line
    $validLine = new TimesheetLineDTO(
        project_id: 1,
        project_task_id: null,
        date: now(),
        hours: '8', // > 0
        description: 'Work',
        is_billable: true
    );

    expect($this->service->validateTimesheetLines([$validLine]))->toBeTrue();

    // Invalid line (0 hours)
    $invalidLine = new TimesheetLineDTO(
        project_id: 1,
        project_task_id: null,
        date: now(),
        hours: '0', // Invalid
        description: 'Work',
        is_billable: true
    );

    expect($this->service->validateTimesheetLines([$invalidLine]))->toBeFalse();
});
