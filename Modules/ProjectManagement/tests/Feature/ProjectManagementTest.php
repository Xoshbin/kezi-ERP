<?php

use App\Models\Company;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\AnalyticAccount;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;
use Modules\Foundation\Models\Currency;
use Modules\ProjectManagement\Actions\CreateProjectAction;
use Modules\ProjectManagement\Actions\CreateProjectBudgetAction;
use Modules\ProjectManagement\Actions\UpdateProjectAction;
use Modules\ProjectManagement\DataTransferObjects\BudgetLineDTO;
use Modules\ProjectManagement\DataTransferObjects\CreateProjectBudgetDTO;
use Modules\ProjectManagement\DataTransferObjects\CreateProjectDTO;
use Modules\ProjectManagement\DataTransferObjects\UpdateProjectDTO;
use Modules\ProjectManagement\Enums\ProjectStatus;
use Modules\ProjectManagement\Models\Project;
use Modules\ProjectManagement\Services\ProjectService;

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
        budget_amount: 5000000, // 50,000.00
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
        ->name->toBe('PROJ-001: New Marketing Project');
});

it('updates project details', function () {
    $project = Project::factory()->create(['company_id' => $this->company->id]);

    $dto = new UpdateProjectDTO(
        name: 'Updated Project Name',
        code: $project->code,
        description: 'Updated Description',
        status: ProjectStatus::Active,
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

    $account = Account::factory()->create(['company_id' => $this->company->id, 'type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense]);
    $journalEntry = JournalEntry::factory()->create(['company_id' => $this->company->id]);

    // Create an expense (Debit) linked to the project's analytic account
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'analytic_account_id' => $project->analytic_account_id,
        'account_id' => $account->id,
        'debit' => 100000, // 1000.00
        'credit' => 0,
    ]);

    // Create another expense
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'analytic_account_id' => $project->analytic_account_id,
        'account_id' => $account->id,
        'debit' => 50000, // 500.00
        'credit' => 0,
    ]);

    // Total cost should be 1500.00
    $totalCost = $project->getTotalActualCost();

    expect($totalCost->getAmount()->toInt())->toBe(150000);
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
        project_id: $project->id,
        name: 'Initial Budget',
        start_date: now(),
        end_date: now()->addMonth(),
        lines: [
            new BudgetLineDTO(
                account_id: Account::factory()->create(['company_id' => $this->company->id, 'type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense])->id,
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
        'account_id' => Account::factory()->create(['company_id' => $this->company->id, 'type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense])->id,
        'debit' => 200000, // 2000.00
        'credit' => 0,
    ]);

    // Variance = Budget (5000) - Actual (2000) = 3000
    $variance = $project->getBudgetVariance();

    expect($variance->getAmount()->toInt())->toBe(300000);
});
