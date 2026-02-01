<?php

use App\Models\Company;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\AnalyticAccount;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Accounting\Models\JournalEntryLine;
use Kezi\Foundation\Models\Currency;
use Kezi\ProjectManagement\Actions\CreateProjectAction;
use Kezi\ProjectManagement\Actions\CreateProjectBudgetAction;
use Kezi\ProjectManagement\Actions\UpdateProjectAction;
use Kezi\ProjectManagement\DataTransferObjects\CreateProjectBudgetDTO;
use Kezi\ProjectManagement\DataTransferObjects\CreateProjectDTO;
use Kezi\ProjectManagement\DataTransferObjects\ProjectBudgetLineDTO;
use Kezi\ProjectManagement\DataTransferObjects\UpdateProjectDTO;
use Kezi\ProjectManagement\Enums\BillingType;
use Kezi\ProjectManagement\Enums\ProjectStatus;
use Kezi\ProjectManagement\Models\Project;
use Kezi\ProjectManagement\Services\ProjectService;

beforeEach(function () {
    $this->currency = Currency::factory()->create(['code' => 'IQD', 'name' => 'Iraqi Dinar', 'symbol' => 'IQD']);
    $this->company = Company::factory()->create([
        'currency_id' => $this->currency->id,
    ]);
});

it('creates project with analytic account auto-creation', function () {
    $dto = new CreateProjectDTO(
        company_id: $this->company->id,
        name: 'New Marketing Project',
        code: 'PROJ-001',
        description: 'Q1 Marketing Campaign',
        manager_id: null,
        customer_id: null,
        start_date: null,
        end_date: null,
        budget_amount: null,
        billing_type: BillingType::TimeAndMaterials,
        is_billable: true,
    );

    $action = app(CreateProjectAction::class);
    $project = $action->execute($dto);

    expect($project)
        ->toBeInstanceOf(Project::class)
        ->name->toBe('New Marketing Project')
        ->code->toBe('PROJ-001')
        ->analytic_account_id->not->toBeNull();

    $analyticAccount = AnalyticAccount::find($project->analytic_account_id);
    expect($analyticAccount)
        ->toBeInstanceOf(AnalyticAccount::class)
        ->name->toBe('New Marketing Project');
});

it('updates project details', function () {
    $project = Project::factory()->create(['company_id' => $this->company->id]);

    $dto = new UpdateProjectDTO(
        name: 'Updated Project Name',
        code: $project->code,
        description: 'Updated Description',
        status: ProjectStatus::Active,
        manager_id: null,
        customer_id: null,
        start_date: null,
        end_date: null,
        budget_amount: null,
        billing_type: BillingType::TimeAndMaterials,
        is_billable: true,
    );

    $action = app(UpdateProjectAction::class);
    $updatedProject = $action->execute($project, $dto);

    expect($updatedProject)
        ->name->toBe('Updated Project Name')
        ->description->toBe('Updated Description')
        ->status->toBe(ProjectStatus::Active);
});

it('exercises project workflow transitions', function () {
    $project = Project::factory()->create([
        'company_id' => $this->company->id,
        'status' => ProjectStatus::Draft,
    ]);

    $service = app(ProjectService::class);

    // Draft -> Active
    $service->activateProject($project);
    expect($project->refresh()->status)->toBe(ProjectStatus::Active);

    // Active -> Completed
    $service->completeProject($project);
    expect($project->refresh()->status)->toBe(ProjectStatus::Completed);
});

it('calculates project cost from journal entries', function () {
    $project = Project::factory()->create(['company_id' => $this->company->id]);
    // Ensure analytic account exists
    if (! $project->analyticAccount) {
        $analyticAccount = AnalyticAccount::factory()->create([
            'company_id' => $this->company->id,
            'name' => $project->name,
        ]);
        $project->analytic_account_id = $analyticAccount->id;
        $project->save();
    }

    $account = Account::factory()->create(['company_id' => $this->company->id, 'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Expense]);
    $journalEntry = JournalEntry::factory()->create(['company_id' => $this->company->id]);

    // Create an expense (Debit) linked to the project's analytic account
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'analytic_account_id' => $project->analytic_account_id,
        'account_id' => $account->id,
        'debit' => \Brick\Money\Money::ofMinor(100000, $this->currency->code), // 1000.00
        'credit' => \Brick\Money\Money::ofMinor(0, $this->currency->code),
    ]);

    // Create another expense
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'analytic_account_id' => $project->analytic_account_id,
        'account_id' => $account->id,
        'debit' => \Brick\Money\Money::ofMinor(50000, $this->currency->code), // 500.00
        'credit' => \Brick\Money\Money::ofMinor(0, $this->currency->code),
    ]);

    // Total cost should be 1500.00
    $totalCost = $project->refresh()->getTotalActualCost();

    expect($totalCost->getMinorAmount()->toInt())->toBe(150000);
});

it('calculates budget variance', function () {
    $project = Project::factory()->create(['company_id' => $this->company->id]);
    if (! $project->analyticAccount) {
        $analyticAccount = AnalyticAccount::factory()->create([
            'company_id' => $this->company->id,
            'name' => $project->name,
        ]);
        $project->analytic_account_id = $analyticAccount->id;
        $project->save();
    }

    // Create a budget of 5000.00
    $budgetDto = new CreateProjectBudgetDTO(
        company_id: $this->company->id,
        project_id: $project->id,
        name: 'Initial Budget',
        start_date: now(),
        end_date: now()->addMonth(),
        lines: [
            new ProjectBudgetLineDTO(
                account_id: Account::factory()->create(['company_id' => $this->company->id, 'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Expense])->id,
                budgeted_amount: 500000 // 5000.00
            ),
        ]
    );
    app(CreateProjectBudgetAction::class)->execute($budgetDto);

    // Create Actual Cost of 2000.00
    $journalEntry = JournalEntry::factory()->create(['company_id' => $this->company->id]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'analytic_account_id' => $project->analytic_account_id,
        'account_id' => Account::factory()->create(['company_id' => $this->company->id, 'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Expense])->id,
        'debit' => \Brick\Money\Money::ofMinor(200000, $this->currency->code), // 2000.00
        'credit' => \Brick\Money\Money::ofMinor(0, $this->currency->code),
    ]);

    // Variance = Budget (5000) - Actual (2000) = 3000
    $variance = $project->refresh()->getBudgetVariance();

    expect($variance->getMinorAmount()->toInt())->toBe(300000);
});
