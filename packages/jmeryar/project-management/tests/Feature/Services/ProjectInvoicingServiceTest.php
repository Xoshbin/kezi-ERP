<?php

namespace Jmeryar\ProjectManagement\Tests\Feature\Services;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\ProjectManagement\DataTransferObjects\CreateProjectInvoiceDTO;
use Jmeryar\ProjectManagement\Models\Project;
use Jmeryar\ProjectManagement\Models\ProjectInvoice;
use Jmeryar\ProjectManagement\Models\Timesheet;
use Jmeryar\ProjectManagement\Models\TimesheetLine;
use Jmeryar\ProjectManagement\Services\ProjectInvoicingService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->service = app(ProjectInvoicingService::class);
    // \Log::info('Starting test');
});

it('generates a project invoice record', function () {
    $project = Project::factory()->create(['company_id' => $this->company->id]);

    $dto = new CreateProjectInvoiceDTO(
        company_id: $this->company->id,
        project_id: $project->id,
        period_start: now()->startOfMonth(),
        period_end: now()->endOfMonth(),
        include_labor: true,
        include_expenses: true
    );

    $invoice = $this->service->generateInvoice($dto);

    expect($invoice)->toBeInstanceOf(ProjectInvoice::class)
        ->and($invoice->project_id)->toBe($project->id)
        ->and($invoice->status)->toBe('draft');
    // amounts are 0 by default without data

});

it('calculates labor costs correctly', function () {
    $project = Project::factory()->create(['company_id' => $this->company->id]);

    // Create approved timesheets
    $timesheet = Timesheet::factory()->create([
        'company_id' => $this->company->id,
        'status' => 'approved',
        'start_date' => now()->startOfMonth(),
        'end_date' => now()->endOfMonth(),
    ]);

    // Add billable lines
    TimesheetLine::factory()->create([
        'timesheet_id' => $timesheet->id,
        'project_id' => $project->id,
        'date' => now()->startOfMonth()->addDay(),
        'hours' => 5,
        'is_billable' => true,
    ]);

    TimesheetLine::factory()->create([
        'timesheet_id' => $timesheet->id,
        'project_id' => $project->id,
        'date' => now()->startOfMonth()->addDay(),
        'hours' => 3,
        'is_billable' => true,
    ]);

    // Add non-billable line (should be ignored)
    TimesheetLine::factory()->create([
        'timesheet_id' => $timesheet->id,
        'project_id' => $project->id,
        'date' => now()->startOfMonth()->addDay(),
        'hours' => 2,
        'is_billable' => false,
    ]);

    // Service assumes rate of 100 for now. 5 + 3 = 8 hours. 8 * 100 = 800.
    $cost = $this->service->calculateLaborCosts($project, now()->startOfMonth(), now()->endOfMonth());

    expect($cost->getAmount()->toFloat())->toBe(800.00);
});

it('creates a customer sales invoice from project invoice', function () {
    // 1. Setup Data
    $customer = Partner::factory()->customer()->create(['company_id' => $this->company->id]);
    $project = Project::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'name' => 'Billable Project',
    ]);

    // Create Income Account needed by service
    $incomeAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::Income,
    ]);

    // Create Project Invoice
    $projectInvoice = ProjectInvoice::factory()->create([
        'company_id' => $this->company->id,
        'project_id' => $project->id,
        'labor_amount' => Money::ofMinor(100000, $this->company->currency->code),
        'expense_amount' => Money::ofMinor(50000, $this->company->currency->code),
        'status' => 'draft',
        'period_start' => now()->startOfMonth(),
        'period_end' => now()->endOfMonth(),
    ]);

    // 2. Execute
    $salesInvoice = $this->service->createCustomerInvoice($projectInvoice);

    // 3. Assert
    expect($salesInvoice)->toBeInstanceOf(\Jmeryar\Sales\Models\Invoice::class)
        ->and($salesInvoice->customer_id)->toBe($customer->id)
        ->and($salesInvoice->company_id)->toBe($this->company->id);

    // Verify invoice lines
    $lines = $salesInvoice->invoiceLines;
    expect($lines)->toHaveCount(2); // One for labor, one for expense

    $laborLine = $lines->first(fn ($line) => str_contains($line->description, 'Project Labor'));
    expect($laborLine)->not->toBeNull()
        ->and($laborLine->subtotal->getMinorAmount()->toInt())->toBe(100000);

    $expenseLine = $lines->first(fn ($line) => str_contains($line->description, 'Project Expenses'));
    expect($expenseLine)->not->toBeNull()
        ->and($expenseLine->subtotal->getMinorAmount()->toInt())->toBe(50000);

    // Verify Project Invoice Updated
    $projectInvoice->refresh();
    expect($projectInvoice->status)->toBe('invoiced')
        ->and($projectInvoice->invoice_id)->toBe($salesInvoice->id);
});

it('prevents multiple invoicing', function () {
    $projectInvoice = ProjectInvoice::factory()->create([
        'company_id' => $this->company->id,
        'status' => 'invoiced',
        'invoice_id' => \Jmeryar\Sales\Models\Invoice::factory()->create(['company_id' => $this->company->id])->id,
    ]);

    $this->service->createCustomerInvoice($projectInvoice);
})->throws(\Exception::class, 'Project invoice is already invoiced.');
