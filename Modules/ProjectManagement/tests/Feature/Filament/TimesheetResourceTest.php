<?php

namespace Modules\ProjectManagement\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HR\Models\Employee;
use Modules\ProjectManagement\Enums\TimesheetStatus;
use Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Timesheets\Pages\CreateTimesheet;
use Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Timesheets\Pages\ListTimesheets;
use Modules\ProjectManagement\Models\Project;
use Modules\ProjectManagement\Models\ProjectTask;
use Modules\ProjectManagement\Models\Timesheet;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setCurrentPanel('jmeryar');
    Filament::setTenant($this->company);
    $this->actingAs($this->user);

    $this->user->update(['current_company_id' => $this->company->id]);
    $this->user->refresh();

    $this->employee = Employee::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
    ]);

    $this->project = Project::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->task = ProjectTask::factory()->create([
        'project_id' => $this->project->id,
        'company_id' => $this->company->id,
    ]);
});

it('can render the list page', function () {
    $timesheets = Timesheet::factory()->count(3)->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
    ]);

    livewire(ListTimesheets::class)
        ->assertOk()
        ->assertCanSeeTableRecords($timesheets);
});

it('can create a timesheet with lines', function () {
    $undoRepeaterFake = \Filament\Forms\Components\Repeater::fake();

    livewire(CreateTimesheet::class)
        ->fillForm([
            'employee_id' => $this->employee->id,
            'start_date' => now()->startOfWeek()->format('Y-m-d'),
            'end_date' => now()->endOfWeek()->format('Y-m-d'),
            'lines' => [
                [
                    'project_id' => $this->project->id,
                    'project_task_id' => $this->task->id,
                    'date' => now()->format('Y-m-d'),
                    'hours' => 8,
                    'description' => 'Worked on task',
                    'is_billable' => true,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $undoRepeaterFake();

    $this->assertDatabaseHas('timesheets', [
        'employee_id' => $this->employee->id,
    ]);

    $this->assertDatabaseHas('timesheet_lines', [
        'project_id' => $this->project->id,
        'hours' => 8,
    ]);
});

it('can approve a timesheet via table action', function () {
    $timesheet = Timesheet::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'status' => TimesheetStatus::Submitted,
    ]);

    livewire(ListTimesheets::class)
        ->callTableAction('approve', $timesheet)
        ->assertHasNoTableActionErrors();

    expect($timesheet->refresh()->status)->toBe(TimesheetStatus::Approved);
});

it('can reject a timesheet via table action', function () {
    $timesheet = Timesheet::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'status' => TimesheetStatus::Submitted,
    ]);

    livewire(ListTimesheets::class)
        ->callTableAction('reject', $timesheet, data: [
            'reason' => 'Not enough detail',
        ])
        ->assertHasNoTableActionErrors();

    expect($timesheet->refresh()->status)->toBe(TimesheetStatus::Rejected);
});
