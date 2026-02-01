<?php

use App\Models\Company;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\AnalyticAccount;
use Jmeryar\Accounting\Models\JournalEntry;
use Jmeryar\Accounting\Models\JournalEntryLine;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\ProjectManagement\Actions\CreateProjectInvoiceAction;
use Jmeryar\ProjectManagement\DataTransferObjects\CreateProjectInvoiceDTO;
use Jmeryar\ProjectManagement\Models\Project;
use Jmeryar\ProjectManagement\Models\ProjectInvoice;
use Jmeryar\ProjectManagement\Models\Timesheet;
use Jmeryar\ProjectManagement\Models\TimesheetLine;
use Jmeryar\ProjectManagement\Services\ProjectInvoicingService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->currency = Currency::factory()->create(['code' => 'USD', 'decimal_places' => 2]);
    $this->company = Company::factory()->create([
        'currency_id' => $this->currency->id,
    ]);
    $this->project = Project::factory()->create(['company_id' => $this->company->id]);

    if (! $this->project->analyticAccount) {
        $analyticAccount = AnalyticAccount::factory()->create([
            'company_id' => $this->company->id,
            'name' => $this->project->name,
        ]);
        $this->project->analytic_account_id = $analyticAccount->id;
        $this->project->save();
    }
});

it('generates invoice from timesheet hours', function () {
    // Create approved timesheets
    $employee = \Jmeryar\HR\Models\Employee::factory()->create(['company_id' => $this->company->id]);
    $timesheet = Timesheet::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $employee->id,
        'status' => \Jmeryar\ProjectManagement\Enums\TimesheetStatus::Approved,
        'start_date' => now()->startOfMonth(),
        'end_date' => now()->endOfMonth(),
    ]);

    TimesheetLine::factory()->create([
        'company_id' => $this->company->id,
        'timesheet_id' => $timesheet->id,
        'project_id' => $this->project->id,
        'date' => now()->startOfMonth(),
        'hours' => 10,
        'is_billable' => true,
    ]);

    $dto = new CreateProjectInvoiceDTO(
        company_id: $this->company->id,
        project_id: $this->project->id,
        period_start: now()->startOfMonth(),
        period_end: now()->endOfMonth(),
        include_labor: true,
        include_expenses: false
    );

    $this->project->update(['hourly_rate' => 500]); // 500 per hour

    $action = app(CreateProjectInvoiceAction::class);
    $projectInvoice = $action->execute($dto);

    expect($projectInvoice)
        ->toBeInstanceOf(ProjectInvoice::class)
        ->labor_amount->getMinorAmount()->toInt()->toBe(500000); // 10 hours * 500 = 5000 -> 500000 cents
});

it('generates invoice from project expenses', function () {
    $account = Account::factory()->create(['company_id' => $this->company->id, 'type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::Expense]);

    // Create billable expense (Journal Entry)
    $journalEntry = JournalEntry::factory()->create(['company_id' => $this->company->id, 'entry_date' => now()->startOfMonth()]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'analytic_account_id' => $this->project->analytic_account_id,
        'account_id' => $account->id,
        'debit' => 20000, // 200.00
        'credit' => 0,
    ]);

    $dto = new CreateProjectInvoiceDTO(
        company_id: $this->company->id,
        project_id: $this->project->id,
        period_start: now()->startOfMonth(),
        period_end: now()->endOfMonth(),
        include_labor: false,
        include_expenses: true
    );

    $projectInvoice = app(CreateProjectInvoiceAction::class)->execute($dto);

    expect($projectInvoice)
        ->expense_amount->getAmount()->toInt()->toBe(20000);
});

it('creates customer invoice from project invoice', function () {
    $this->project->update(['customer_id' => \Jmeryar\Foundation\Models\Partner::factory()->create(['company_id' => $this->company->id])->id]);

    $projectInvoice = ProjectInvoice::factory()->create([
        'project_id' => $this->project->id,
        'company_id' => $this->company->id,
        'labor_amount' => 100000,
        'total_amount' => 100000,
        'status' => 'draft',
    ]);

    $invoice = app(ProjectInvoicingService::class)->createCustomerInvoice($projectInvoice);

    expect($invoice)
        ->toBeInstanceOf(\Jmeryar\Sales\Models\Invoice::class)
        ->total_amount->getAmount()->toInt()->toBe(100000);

    expect($projectInvoice->refresh())
        ->invoice_id->toBe($invoice->id)
        ->status->toBe('invoiced');
});
