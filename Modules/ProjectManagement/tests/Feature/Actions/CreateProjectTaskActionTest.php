<?php

namespace Modules\ProjectManagement\Tests\Feature\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HR\Models\Employee;
use Modules\ProjectManagement\Actions\CreateProjectTaskAction;
use Modules\ProjectManagement\DataTransferObjects\CreateProjectTaskDTO;
use Modules\ProjectManagement\Models\Project;
use Modules\ProjectManagement\Models\ProjectTask;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CreateProjectTaskAction::class);
});

it('creates a project task successfully', function () {
    $project = Project::factory()->create(['company_id' => $this->company->id]);
    $employee = Employee::factory()->create(['company_id' => $this->company->id]);

    $dto = new CreateProjectTaskDTO(
        company_id: $this->company->id,
        project_id: $project->id,
        parent_task_id: null,
        name: 'Test Task',
        description: 'Detail description',
        assigned_to: $employee->id,
        start_date: now(),
        due_date: now()->addDays(5),
        estimated_hours: '10',
        sequence: 1
    );

    $task = $this->action->execute($dto);

    expect($task)->toBeInstanceOf(ProjectTask::class);
    expect($task->name)->toBe('Test Task');
    expect($task->project_id)->toBe($project->id);
    expect($task->assigned_to)->toBe($employee->id);
    expect($task->estimated_hours)->toBe('10');
    expect($task->sequence)->toBe(1);

    $this->assertDatabaseHas('project_tasks', [
        'id' => $task->id,
        'name' => 'Test Task',
        'project_id' => $project->id,
    ]);
});

it('creates a subtask successfully', function () {
    $project = Project::factory()->create(['company_id' => $this->company->id]);
    $parentTask = ProjectTask::factory()->create([
        'company_id' => $this->company->id,
        'project_id' => $project->id,
    ]);

    $dto = new CreateProjectTaskDTO(
        company_id: $this->company->id,
        project_id: $project->id,
        parent_task_id: $parentTask->id,
        name: 'Sub Task',
        description: 'Sub description',
        assigned_to: null,
        start_date: now(),
        due_date: now()->addDays(5),
        estimated_hours: '5',
        sequence: 2
    );

    $task = $this->action->execute($dto);

    expect($task->parent_task_id)->toBe($parentTask->id);
    $this->assertDatabaseHas('project_tasks', [
        'id' => $task->id,
        'parent_task_id' => $parentTask->id,
    ]);
});
