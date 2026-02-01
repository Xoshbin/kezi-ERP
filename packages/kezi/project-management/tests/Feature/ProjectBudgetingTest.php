<?php

use App\Models\Company;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\AnalyticAccount;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Accounting\Models\JournalEntryLine;
use Kezi\Foundation\Models\Currency;
use Kezi\ProjectManagement\Actions\CreateProjectBudgetAction;
use Kezi\ProjectManagement\DataTransferObjects\CreateProjectBudgetDTO;
use Kezi\ProjectManagement\DataTransferObjects\ProjectBudgetLineDTO;
use Kezi\ProjectManagement\Models\Project;
use Kezi\ProjectManagement\Models\ProjectBudget;
use Kezi\ProjectManagement\Services\ProjectBudgetService;

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
    $account1 = Account::factory()->create(['company_id' => $this->company->id, 'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Expense]);
    $account2 = Account::factory()->create(['company_id' => $this->company->id, 'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Expense]);

    $dto = new CreateProjectBudgetDTO(
        company_id: $this->company->id,
        project_id: $this->project->id,
        name: 'Q1 Budget',
        start_date: today(),
        end_date: today()->addMonth(),
        lines: [
            new ProjectBudgetLineDTO(
                account_id: $account1->id,
                budgeted_amount: 100000 // 1000.00
            ),
            new ProjectBudgetLineDTO(
                account_id: $account2->id,
                budgeted_amount: 200000 // 2000.00
            ),
        ]
    );

    $action = app(CreateProjectBudgetAction::class);
    $budget = $action->execute($dto);

    expect($budget)
        ->toBeInstanceOf(ProjectBudget::class)
        ->total_budget->getMinorAmount()->toInt()->toBe(300000) // 3000.00
        ->lines->count()->toBe(2);
});

it('updates actuals from journal entries', function () {
    $account = Account::factory()->create(['company_id' => $this->company->id, 'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Expense]);

    // Create Budget
    $budgetDto = new CreateProjectBudgetDTO(
        company_id: $this->company->id,
        project_id: $this->project->id,
        name: 'Test Budget',
        start_date: today(),
        end_date: today()->addMonth(),
        lines: [
            new ProjectBudgetLineDTO(
                account_id: $account->id,
                budgeted_amount: 500000 // 5000.00
            ),
        ]
    );
    $budget = app(CreateProjectBudgetAction::class)->execute($budgetDto);

    // Create Actual Expense (Journal Entry) linked to project analytic account AND budget account
    $journalEntry = JournalEntry::factory()->create(['company_id' => $this->company->id, 'entry_date' => now()]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'analytic_account_id' => $this->project->analytic_account_id,
        'account_id' => $account->id,
        'debit' => \Brick\Money\Money::ofMinor(150000, $this->currency->code), // 1500.00
        'credit' => \Brick\Money\Money::ofMinor(0, $this->currency->code),
    ]);

    // Run service to update actuals
    app(ProjectBudgetService::class)->updateActuals($budget);

    $budgetLine = $budget->lines->first();
    expect($budgetLine->refresh())
        ->actual_amount->getMinorAmount()->toInt()->toBe(150000);

    expect($budget->refresh())
        ->total_actual->getMinorAmount()->toInt()->toBe(150000);
});

it('calculates budget variance correctly', function () {
    $account = Account::factory()->create(['company_id' => $this->company->id, 'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Expense]);

    $budgetDto = new CreateProjectBudgetDTO(
        company_id: $this->company->id,
        project_id: $this->project->id,
        name: 'Variance Test',
        start_date: today(),
        end_date: today()->addMonth(),
        lines: [
            new ProjectBudgetLineDTO(
                account_id: $account->id,
                budgeted_amount: 100000 // 1000.00
            ),
        ]
    );
    $budget = app(CreateProjectBudgetAction::class)->execute($budgetDto);

    // Spend 1200.00 (Over budget)
    $journalEntry = JournalEntry::factory()->create(['company_id' => $this->company->id, 'entry_date' => now()]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'analytic_account_id' => $this->project->analytic_account_id,
        'account_id' => $account->id,
        'debit' => \Brick\Money\Money::ofMinor(120000, $this->currency->code), // 1200.00
        'credit' => \Brick\Money\Money::ofMinor(0, $this->currency->code),
    ]);

    app(ProjectBudgetService::class)->updateActuals($budget);

    // Variance = Budget (1000) - Actual (1200) = -200
    // But getBudgetVariance usually returns Budget - Actual
    // Or we check variance on the line/budget model if it exists, or calculate it.
    // The Project model has getBudgetVariance() which is global.
    // Let's check the Project's variance.

    $variance = $this->project->refresh()->getBudgetVariance();
    expect($variance->getMinorAmount()->toInt())->toBe(-20000); // -20.000 IQD
});

it('generates budget utilization report', function () {
    $account = Account::factory()->create(['company_id' => $this->company->id, 'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Expense]);

    $budgetDto = new CreateProjectBudgetDTO(
        company_id: $this->company->id,
        project_id: $this->project->id,
        name: 'Utilization Test',
        start_date: today(),
        end_date: today()->addMonth(),
        lines: [
            new ProjectBudgetLineDTO(
                account_id: $account->id,
                budgeted_amount: 100000 // 1000.00
            ),
        ]
    );
    $budget = app(CreateProjectBudgetAction::class)->execute($budgetDto);

    // Spend 500.00 (50% utilization)
    $journalEntry = JournalEntry::factory()->create(['company_id' => $this->company->id, 'entry_date' => now()]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'analytic_account_id' => $this->project->analytic_account_id,
        'account_id' => $account->id,
        'debit' => \Brick\Money\Money::ofMinor(50000, $this->currency->code), // 500.00
        'credit' => \Brick\Money\Money::ofMinor(0, $this->currency->code),
    ]);
    app(ProjectBudgetService::class)->updateActuals($budget);

    $utilization = app(ProjectBudgetService::class)->getBudgetUtilizationPercentage($budget);

    expect($utilization)->toBe(50.0);
});
