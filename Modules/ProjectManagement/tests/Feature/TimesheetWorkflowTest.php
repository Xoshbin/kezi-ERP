<?php

use App\Models\Company;
use App\Models\User;
use Modules\HR\Models\Employee;
use Modules\ProjectManagement\Actions\ApproveTimesheetAction;
use Modules\ProjectManagement\Actions\CreateTimesheetAction;
use Modules\ProjectManagement\Actions\RejectTimesheetAction;
use Modules\ProjectManagement\Actions\SubmitTimesheetAction;
use Modules\ProjectManagement\DataTransferObjects\CreateTimesheetDTO;
use Modules\ProjectManagement\DataTransferObjects\TimesheetLineDTO;
use Modules\ProjectManagement\Enums\TimesheetStatus;
use Modules\ProjectManagement\Models\Project;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);
use Modules\ProjectManagement\Models\ProjectTask;
use Modules\ProjectManagement\Models\Timesheet;

beforeEach(function () {
    $this->currency = \Modules\Foundation\Models\Currency::factory()->create(['code' => 'IQD', 'name' => 'Iraqi Dinar', 'symbol' => 'IQD']);
    $this->company = Company::factory()->create([
        'currency_id' => $this->currency->id,
    ]);
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->employee = Employee::factory()->create(['company_id' => $this->company->id, 'user_id' => $this->user->id]);
    $this->project = Project::factory()->create(['company_id' => $this->company->id]);
    $this->task = ProjectTask::factory()->create([
        'company_id' => $this->company->id,
        'project_id' => $this->project->id,
        'estimated_hours' => 10,
    ]);
});

it('creates timesheet with lines', function () {
    $dto = new CreateTimesheetDTO(
        company_id: $this->company->id,
        employee_id: $this->employee->id,
        start_date: now()->startOfWeek(),
        end_date: now()->endOfWeek(),
        status: TimesheetStatus::Draft,
        lines: [
            new TimesheetLineDTO(
                project_id: $this->project->id,
                project_task_id: $this->task->id,
                date: now()->startOfWeek(),
                hours: 4.5,
                description: 'Initial work',
                is_billable: true
            ),
            new TimesheetLineDTO(
                project_id: $this->project->id,
                project_task_id: $this->task->id,
                date: now()->startOfWeek()->addDay(),
                hours: 3.5,
                description: 'Follow up',
                is_billable: true
            ),
        ]
    );

    $action = app(CreateTimesheetAction::class);
    $timesheet = $action->execute($dto);

    expect($timesheet)
        ->toBeInstanceOf(Timesheet::class)
        ->status->toBe(TimesheetStatus::Draft)
        ->total_hours->toBe(8.0) // 4.5 + 3.5
        ->lines->count()->toBe(2);
});

it('submits timesheet for approval', function () {
    $timesheet = Timesheet::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'status' => TimesheetStatus::Draft,
    ]);

    // Add a line so it's valid
    $timesheet->lines()->create([
        'company_id' => $this->company->id,
        'project_id' => $this->project->id,
        'date' => now(),
        'hours' => 8,
    ]);

    $action = app(SubmitTimesheetAction::class);
    $action->execute($timesheet);

    expect($timesheet->refresh())
        ->status->toBe(TimesheetStatus::Submitted);
});

it('approves timesheet and updates task actual hours', function () {
    $timesheet = Timesheet::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'status' => TimesheetStatus::Submitted,
    ]);

    // Add 5 hours to the task
    $timesheet->lines()->create([
        'company_id' => $this->company->id,
        'project_id' => $this->project->id,
        'project_task_id' => $this->task->id,
        'date' => now(),
        'hours' => 5,
    ]);

    $approver = User::factory()->create();
    $approver->companies()->attach($this->company);

    $action = app(ApproveTimesheetAction::class);
    $action->execute($timesheet, $approver);

    expect($timesheet->refresh())
        ->status->toBe(TimesheetStatus::Approved)
        ->approved_by->toBe($approver->id)
        ->approved_at->not->toBeNull();

    expect($this->task->refresh())
        ->actual_hours->toEqual(5.0);
});

it('rejects timesheet with reason', function () {
    $timesheet = Timesheet::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'status' => TimesheetStatus::Submitted,
    ]);

    $action = app(RejectTimesheetAction::class);
    $rejector = User::factory()->create();
    $rejector->companies()->attach($this->company);
    $action->execute($timesheet, $rejector, 'Incomplete description');

    expect($timesheet->refresh())
        ->status->toBe(TimesheetStatus::Rejected)
        ->rejection_reason->toBe('Incomplete description');
});

it('calculates total billable hours', function () {
    $timesheet = Timesheet::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'status' => TimesheetStatus::Approved,
    ]);

    // Billable line: 4 hours
    $timesheet->lines()->create([
        'company_id' => $this->company->id,
        'project_id' => $this->project->id,
        'date' => now(),
        'hours' => 4,
        'is_billable' => true,
    ]);

    // Non-billable line: 2 hours
    $timesheet->lines()->create([
        'company_id' => $this->company->id,
        'project_id' => $this->project->id,
        'date' => now(),
        'hours' => 2,
        'is_billable' => false,
    ]);

    $totalBillable = $this->project->getTotalBillableHours();
    $totalHours = $this->project->getTotalHours();

    // Since our test project only has this timesheet associated
    expect($totalBillable)->toBe(4.0)
        ->and($totalHours)->toBe(6.0);
});
