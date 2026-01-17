<?php

namespace Modules\ProjectManagement\Tests\Feature\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HR\Models\Employee;
use Modules\ProjectManagement\Actions\CreateTimesheetAction;
use Modules\ProjectManagement\DataTransferObjects\CreateTimesheetDTO;
use Modules\ProjectManagement\DataTransferObjects\TimesheetLineDTO;
use Modules\ProjectManagement\Enums\TimesheetStatus;
use Modules\ProjectManagement\Models\Project;
use Modules\ProjectManagement\Models\ProjectTask;
use Modules\ProjectManagement\Models\Timesheet;
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
