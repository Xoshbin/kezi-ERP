<?php

use App\Models\Company;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\AnalyticAccount;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;
use Modules\Foundation\Models\Currency;
use Modules\ProjectManagement\Actions\CreateProjectBudgetAction;
use Modules\ProjectManagement\DataTransferObjects\BudgetLineDTO;
use Modules\ProjectManagement\DataTransferObjects\CreateProjectBudgetDTO;
use Modules\ProjectManagement\Models\Project;
use Modules\ProjectManagement\Models\ProjectBudget;
use Modules\ProjectManagement\Services\ProjectBudgetService;

beforeEach(function () {
    $this->currency = Currency::factory()->create(['code' => 'IQD', 'name' => 'Iraqi Dinar', 'symbol' => 'IQD']);
    $this->company = Company::factory()->create([
        'currency_id' => $this->currency->id,
    ]);
    $this->project = Project::factory()->create(['company_id' => $this->company->id]);

    // Ensure analytic account exists
    if (! $this->project->analyticAccount) {
        $analyticAccount = AnalyticAccount::factory()->create([
            'company_id' => $this->company->id,
            'name' => $this->project->name,
        ]);
        $this->project->analytic_account_id = $analyticAccount->id;
        $this->project->save();
    }
});

it('creates project budget with lines', function () {
    $account1 = Account::factory()->create(['company_id' => $this->company->id, 'type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense]);
    $account2 = Account::factory()->create(['company_id' => $this->company->id, 'type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense]);

    $dto = new CreateProjectBudgetDTO(
        project_id: $this->project->id,
        name: 'Q1 Budget',
        start_date: now(),
        end_date: now()->addMonth(),
        lines: [
            new BudgetLineDTO(
                account_id: $account1->id,
                budgeted_amount: 100000 // 1000.00
            ),
            new BudgetLineDTO(
                account_id: $account2->id,
                budgeted_amount: 200000 // 2000.00
            ),
        ]
    );

    $action = app(CreateProjectBudgetAction::class);
    $budget = $action->execute($dto);

    expect($budget)
        ->toBeInstanceOf(ProjectBudget::class)
        ->total_budget->toBe(300000.0) // 3000.00
        ->lines->count()->toBe(2);
});

it('updates actuals from journal entries', function () {
    $account = Account::factory()->create(['company_id' => $this->company->id, 'type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense]);

    // Create Budget
    $budgetDto = new CreateProjectBudgetDTO(
        project_id: $this->project->id,
        name: 'Test Budget',
        start_date: now(),
        end_date: now()->addMonth(),
        lines: [
            new BudgetLineDTO(
                account_id: $account->id,
                budgeted_amount: 500000 // 5000.00
            ),
        ]
    );
    $budget = app(CreateProjectBudgetAction::class)->execute($budgetDto);

    // Create Actual Expense (Journal Entry) linked to project analytic account AND budget account
    $journalEntry = JournalEntry::factory()->create(['company_id' => $this->company->id]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'analytic_account_id' => $this->project->analytic_account_id,
        'account_id' => $account->id,
        'debit' => 150000, // 1500.00
        'credit' => 0,
    ]);

    // Run service to update actuals
    app(ProjectBudgetService::class)->updateActuals($budget);

    $budgetLine = $budget->lines->first();
    expect($budgetLine->refresh())
        ->actual_amount->getAmount()->toInt()->toBe(150000);

    expect($budget->refresh())
        ->total_actual->getAmount()->toInt()->toBe(150000);
});

it('calculates budget variance correctly', function () {
    $account = Account::factory()->create(['company_id' => $this->company->id, 'type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense]);

    $budgetDto = new CreateProjectBudgetDTO(
        project_id: $this->project->id,
        name: 'Variance Test',
        start_date: now(),
        end_date: now()->addMonth(),
        lines: [
            new BudgetLineDTO(
                account_id: $account->id,
                budgeted_amount: 100000 // 1000.00
            ),
        ]
    );
    $budget = app(CreateProjectBudgetAction::class)->execute($budgetDto);

    // Spend 1200.00 (Over budget)
    $journalEntry = JournalEntry::factory()->create(['company_id' => $this->company->id]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'analytic_account_id' => $this->project->analytic_account_id,
        'account_id' => $account->id,
        'debit' => 120000, // 1200.00
        'credit' => 0,
    ]);

    app(ProjectBudgetService::class)->updateActuals($budget);

    // Variance = Budget (1000) - Actual (1200) = -200
    // But getBudgetVariance usually returns Budget - Actual
    // Or we check variance on the line/budget model if it exists, or calculate it.
    // The Project model has getBudgetVariance() which is global.
    // Let's check the Project's variance.

    $variance = $this->project->getBudgetVariance();
    expect($variance->getAmount()->toInt())->toBe(-20000); // -200.00
});

it('generates budget utilization report', function () {
    $account = Account::factory()->create(['company_id' => $this->company->id, 'type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense]);

    $budgetDto = new CreateProjectBudgetDTO(
        project_id: $this->project->id,
        name: 'Utilization Test',
        start_date: now(),
        end_date: now()->addMonth(),
        lines: [
            new BudgetLineDTO(
                account_id: $account->id,
                budgeted_amount: 100000 // 1000.00
            ),
        ]
    );
    $budget = app(CreateProjectBudgetAction::class)->execute($budgetDto);

    // Spend 500.00 (50% utilization)
    $journalEntry = JournalEntry::factory()->create(['company_id' => $this->company->id]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'analytic_account_id' => $this->project->analytic_account_id,
        'account_id' => $account->id,
        'debit' => 50000, // 500.00
        'credit' => 0,
    ]);
    app(ProjectBudgetService::class)->updateActuals($budget);

    $utilization = app(ProjectBudgetService::class)->getBudgetUtilizationPercentage($budget);

    expect($utilization)->toBe(50.0);
});
