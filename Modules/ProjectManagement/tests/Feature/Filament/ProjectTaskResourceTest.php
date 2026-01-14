<?php

namespace Modules\ProjectManagement\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HR\Models\Employee;
use Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Pages\CreateProjectTask;
use Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Pages\EditProjectTask;
use Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\ProjectTaskResource;
use Modules\ProjectManagement\Models\Project;
use Modules\ProjectManagement\Models\ProjectTask;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setCurrentPanel('jmeryar');
    Filament::setTenant($this->company);
    $this->actingAs($this->user);

    // Create employee record for assignee
    $this->employee = Employee::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    // Create a project
    $this->project = Project::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->user->update(['current_company_id' => $this->company->id]);
    $this->user->refresh();
    $this->actingAs($this->user);
});

it('can render the list page', function () {
    $this->get(ProjectTaskResource::getUrl('index'))->assertSuccessful();
});

it('can render the create page', function () {
    $this->get(ProjectTaskResource::getUrl('create'))->assertSuccessful();
});

it('can create a project task', function () {
    livewire(CreateProjectTask::class)
        ->fillForm([
            'project_id' => $this->project->id,
            'name' => 'Test Task',
            'assigned_to' => $this->employee->id,
            'status' => 'pending',
            'start_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(5)->format('Y-m-d'),
            'estimated_hours' => 10,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('project_tasks', [
        'name' => 'Test Task',
        'project_id' => $this->project->id,
        'assigned_to' => $this->employee->id,
        'status' => 'pending',
        'estimated_hours' => 10,
    ]);
});

it('can render the edit page', function () {
    $task = ProjectTask::factory()->create([
        'company_id' => $this->company->id,
        'project_id' => $this->project->id,
    ]);

    $this->get(ProjectTaskResource::getUrl('edit', ['record' => $task]))
        ->assertSuccessful();
});

it('can edit a project task', function () {
    $task = ProjectTask::factory()->create([
        'company_id' => $this->company->id,
        'project_id' => $this->project->id,
        'name' => 'Original Task Name',
    ]);

    livewire(EditProjectTask::class, [
        'record' => $task->getRouteKey(),
    ])
        ->fillForm([
            'name' => 'Updated Task Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($task->refresh()->name)->toBe('Updated Task Name');
});

it('can soft delete a project task', function () {
    $task = ProjectTask::factory()->create([
        'company_id' => $this->company->id,
        'project_id' => $this->project->id,
    ]);

    livewire(\Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Pages\ListProjectTasks::class)
        ->callTableAction('delete', $task);

    $this->assertSoftDeleted('project_tasks', [
        'id' => $task->id,
    ]);
});

it('can restore a soft deleted project task', function () {
    $task = ProjectTask::factory()->create([
        'company_id' => $this->company->id,
        'project_id' => $this->project->id,
    ]);

    $task->delete();

    livewire(\Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Pages\ListProjectTasks::class)
        ->filterTable('trashed', 'only')
        ->callTableBulkAction('restore', [$task]);

    $this->assertDatabaseHas('project_tasks', [
        'id' => $task->id,
        'deleted_at' => null,
    ]);
});

it('can force delete a project task', function () {
    $task = ProjectTask::factory()->create([
        'company_id' => $this->company->id,
        'project_id' => $this->project->id,
    ]);

    $task->delete();

    livewire(\Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Pages\ListProjectTasks::class)
        ->filterTable('trashed', 'only')
        ->callTableBulkAction('forceDelete', [$task]);

    $this->assertDatabaseMissing('project_tasks', [
        'id' => $task->id,
    ]);
});

it('can filter trashed tasks', function () {
    // Create two tasks - one active, one trashed
    $activeTask = ProjectTask::factory()->create([
        'company_id' => $this->company->id,
        'project_id' => $this->project->id,
        'name' => 'Active Task',
    ]);

    $trashedTask = ProjectTask::factory()->create([
        'company_id' => $this->company->id,
        'project_id' => $this->project->id,
        'name' => 'Trashed Task',
    ]);
    $trashedTask->delete();

    // Test 1: Default view - should only show active tasks (not trashed)
    livewire(\Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Pages\ListProjectTasks::class)
        ->assertCanSeeTableRecords([$activeTask])
        ->assertCountTableRecords(1);

    // Test 2: Filter to show all tasks (including trashed)
    livewire(\Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Pages\ListProjectTasks::class)
        ->filterTable('trashed', 'with')
        ->assertCountTableRecords(2);
});
