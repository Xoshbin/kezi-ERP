<?php

namespace Kezi\ProjectManagement\Tests\Feature\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\HR\Models\Employee;
use Kezi\ProjectManagement\Actions\CreateTimesheetAction;
use Kezi\ProjectManagement\DataTransferObjects\CreateTimesheetDTO;
use Kezi\ProjectManagement\DataTransferObjects\TimesheetLineDTO;
use Kezi\ProjectManagement\Enums\TimesheetStatus;
use Kezi\ProjectManagement\Models\Project;
use Kezi\ProjectManagement\Models\ProjectTask;
use Kezi\ProjectManagement\Models\Timesheet;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CreateTimesheetAction::class);
});

it('creates a timesheet with lines successfully', function () {
    $project = Project::factory()->create(['company_id' => $this->company->id]);
    $task = ProjectTask::factory()->create([
        'company_id' => $this->company->id,
        'project_id' => $project->id,
    ]);
    $employee = Employee::factory()->create(['company_id' => $this->company->id]);

    $lineDto1 = new TimesheetLineDTO(
        project_id: $project->id,
        project_task_id: $task->id,
        date: now(),
        hours: '4',
        description: 'First Line',
        is_billable: true
    );

    $lineDto2 = new TimesheetLineDTO(
        project_id: $project->id,
        project_task_id: null,
        date: now(),
        hours: '2.5',
        description: 'Second Line',
        is_billable: false
    );

    $dto = new CreateTimesheetDTO(
        company_id: $this->company->id,
        employee_id: $employee->id,
        start_date: now()->startOfWeek(),
        end_date: now()->endOfWeek(),
        status: TimesheetStatus::Draft,
        lines: [$lineDto1, $lineDto2]
    );

    $timesheet = $this->action->execute($dto);

    expect($timesheet)->toBeInstanceOf(Timesheet::class);
    expect($timesheet->employee_id)->toBe($employee->id);
    expect($timesheet->status)->toBe(TimesheetStatus::Draft);
    expect($timesheet->lines)->toHaveCount(2);
    expect((float) $timesheet->total_hours)->toBe(6.5);

    $this->assertDatabaseHas('timesheets', [
        'id' => $timesheet->id,
        'employee_id' => $employee->id,
    ]);

    $this->assertDatabaseHas('timesheet_lines', [
        'timesheet_id' => $timesheet->id,
        'project_task_id' => $task->id,
        'hours' => '4',
    ]);
});
