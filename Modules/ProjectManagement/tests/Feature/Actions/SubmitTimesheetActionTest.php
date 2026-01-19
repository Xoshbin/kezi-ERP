<?php

namespace Modules\ProjectManagement\Tests\Feature\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\ProjectManagement\Actions\SubmitTimesheetAction;
use Modules\ProjectManagement\Enums\TimesheetStatus;
use Modules\ProjectManagement\Events\TimesheetSubmitted;
use Modules\ProjectManagement\Models\Timesheet;
use Modules\ProjectManagement\Models\TimesheetLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(SubmitTimesheetAction::class);
});

it('submits a draft timesheet successfully', function () {
    Event::fake();

    $timesheet = Timesheet::factory()->create([
        'company_id' => $this->company->id,
        'status' => TimesheetStatus::Draft,
    ]);

    // Create a line because empty timesheet cannot be submitted
    TimesheetLine::factory()->create([
        'company_id' => $this->company->id,
        'timesheet_id' => $timesheet->id,
    ]);

    $this->action->execute($timesheet);

    expect($timesheet->status)->toBe(TimesheetStatus::Submitted);
    Event::assertDispatched(TimesheetSubmitted::class);
});

it('throws exception when submitting an empty timesheet', function () {
    $timesheet = Timesheet::factory()->create([
        'company_id' => $this->company->id,
        'status' => TimesheetStatus::Draft,
    ]);

    expect(fn () => $this->action->execute($timesheet))
        ->toThrow(\RuntimeException::class, 'Cannot submit an empty timesheet.');
});

it('throws exception when submitting a non-draft timesheet', function () {
    $timesheet = Timesheet::factory()->create([
        'company_id' => $this->company->id,
        'status' => TimesheetStatus::Approved,
    ]);

    expect(fn () => $this->action->execute($timesheet))
        ->toThrow(\RuntimeException::class, 'Only draft timesheets can be submitted.');
});
