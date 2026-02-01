<?php

namespace Jmeryar\ProjectManagement\Tests\Feature\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\ProjectManagement\Actions\CreateProjectInvoiceAction;
use Jmeryar\ProjectManagement\DataTransferObjects\CreateProjectInvoiceDTO;
use Jmeryar\ProjectManagement\Enums\TimesheetStatus;
use Jmeryar\ProjectManagement\Models\Project;
use Jmeryar\ProjectManagement\Models\ProjectInvoice;
use Jmeryar\ProjectManagement\Models\Timesheet;
use Jmeryar\ProjectManagement\Models\TimesheetLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CreateProjectInvoiceAction::class);
    // Actually looking back at step 4: CreateProjectInvoiceAction.php
    $this->action = app(CreateProjectInvoiceAction::class);
});

it('creates a project invoice with labor successfully', function () {
    $project = Project::factory()->create([
        'company_id' => $this->company->id,
        'hourly_rate' => 100,
        'is_billable' => true,
    ]);

    $timesheet = Timesheet::factory()->create([
        'company_id' => $this->company->id,
        'status' => TimesheetStatus::Approved,
        'start_date' => now()->subDays(10),
        'end_date' => now(),
    ]);

    TimesheetLine::factory()->create([
        'company_id' => $this->company->id,
        'timesheet_id' => $timesheet->id,
        'project_id' => $project->id,
        'hours' => 10,
        'date' => now()->subDays(5),
        'is_billable' => true,
    ]);

    $dto = new CreateProjectInvoiceDTO(
        company_id: $this->company->id,
        project_id: $project->id,
        period_start: now()->subMonth(),
        period_end: now(),
        include_labor: true,
        include_expenses: false
    );

    $invoice = $this->action->execute($dto);

    expect($invoice)->toBeInstanceOf(ProjectInvoice::class);
    expect($invoice->labor_amount->getAmount()->toFloat())->toBe(1000.0);
    expect($invoice->total_amount->getAmount()->toFloat())->toBe(1000.0);
    expect($invoice->status)->toBe('draft');
});
