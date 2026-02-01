<?php

namespace Kezi\ProjectManagement\Tests\Feature\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Kezi\ProjectManagement\Actions\RejectTimesheetAction;
use Kezi\ProjectManagement\Enums\TimesheetStatus;
use Kezi\ProjectManagement\Events\TimesheetRejected;
use Kezi\ProjectManagement\Models\Timesheet;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(RejectTimesheetAction::class);
});

it('rejects a submitted timesheet with reason', function () {
    Event::fake();

    $timesheet = Timesheet::factory()->create([
        'company_id' => $this->company->id,
        'status' => TimesheetStatus::Submitted,
    ]);

    $this->action->execute($timesheet, $this->user, 'Incomplete data');

    expect($timesheet->status)->toBe(TimesheetStatus::Rejected);
    expect($timesheet->rejection_reason)->toBe('Incomplete data');

    Event::assertDispatched(TimesheetRejected::class);
});

it('throws exception when rejecting a non-submitted timesheet', function () {
    $timesheet = Timesheet::factory()->create([
        'company_id' => $this->company->id,
        'status' => TimesheetStatus::Draft,
    ]);

    expect(fn () => $this->action->execute($timesheet, $this->user, 'Invalid'))
        ->toThrow(\RuntimeException::class, 'Only submitted timesheets can be rejected.');
});
