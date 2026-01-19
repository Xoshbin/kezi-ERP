<?php

namespace Modules\ProjectManagement\Tests\Feature\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\ProjectManagement\Actions\ApproveTimesheetAction;
use Modules\ProjectManagement\Enums\TimesheetStatus;
use Modules\ProjectManagement\Events\TimesheetApproved;
use Modules\ProjectManagement\Models\ProjectTask;
use Modules\ProjectManagement\Models\Timesheet;
use Modules\ProjectManagement\Models\TimesheetLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(ApproveTimesheetAction::class);
});

it('approves a submitted timesheet and updates task hours', function () {
    Event::fake();

    $task = ProjectTask::factory()->create([
        'company_id' => $this->company->id,
        'actual_hours' => '0',
    ]);

    $timesheet = Timesheet::factory()->create([
        'company_id' => $this->company->id,
        'status' => TimesheetStatus::Submitted,
    ]);

    TimesheetLine::factory()->create([
        'company_id' => $this->company->id,
        'timesheet_id' => $timesheet->id,
        'project_task_id' => $task->id,
        'hours' => '5.5',
    ]);

    $this->action->execute($timesheet, $this->user);

    expect($timesheet->status)->toBe(TimesheetStatus::Approved);
    expect($timesheet->approved_by)->toBe($this->user->id);
    expect((float) $task->fresh()->actual_hours)->toBe(5.5);

    Event::assertDispatched(TimesheetApproved::class);
});

it('throws exception when approving a non-submitted timesheet', function () {
    $timesheet = Timesheet::factory()->create([
        'company_id' => $this->company->id,
        'status' => TimesheetStatus::Draft,
    ]);

    expect(fn () => $this->action->execute($timesheet, $this->user))
        ->toThrow(\RuntimeException::class, 'Only submitted timesheets can be approved.');
});
