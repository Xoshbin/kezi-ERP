<?php

namespace Kezi\ProjectManagement\Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\AnalyticAccount;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Accounting\Models\JournalEntryLine;
use Kezi\ProjectManagement\DataTransferObjects\CreateProjectBudgetDTO;
use Kezi\ProjectManagement\Models\Project;
use Kezi\ProjectManagement\Models\ProjectBudget;
use Kezi\ProjectManagement\Models\ProjectBudgetLine;
use Kezi\ProjectManagement\Services\ProjectBudgetService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->service = app(ProjectBudgetService::class);
});

it('creates project budget successfully', function () {
    $project = Project::factory()->create(['company_id' => $this->company->id]);

    $dto = new CreateProjectBudgetDTO(
        company_id: $this->company->id,
        project_id: $project->id,
        name: 'Test Budget',
        start_date: now(),
        end_date: now()->addYear(),
        lines: [] // Assuming DTO handles empty lines or we can check simple case
    );

    $budget = $this->service->createBudget($dto);

    expect($budget)->toBeInstanceOf(ProjectBudget::class)
        ->and($budget->name)->toBe('Test Budget')
        ->and($budget->project_id)->toBe($project->id);
});

it('updates actuals from journal entries', function () {
    // 1. Setup Analytic Account and Project
    $analyticAccount = AnalyticAccount::factory()->create(['company_id' => $this->company->id]);
    $project = Project::factory()->create([
        'company_id' => $this->company->id,
        'analytic_account_id' => $analyticAccount->id,
    ]);

    // 2. Setup Accounts (Expense)
    $expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Expense,
    ]);

    // 3. Create Budget and Line
    $budget = ProjectBudget::factory()->create([
        'company_id' => $this->company->id,
        'project_id' => $project->id,
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
    ]);

    $budgetLine = ProjectBudgetLine::factory()->create([
        'company_id' => $this->company->id,
        'project_budget_id' => $budget->id,
        'account_id' => $expenseAccount->id,
        'budgeted_amount' => 1000, // 1000.00
    ]);

    // 4. Create Journal Entry with Analytic Account
    $journalEntry = JournalEntry::factory()->create([
        'company_id' => $this->company->id,
        'entry_date' => now()->month(6), // Within budget period
    ]);

    // Add debit line (cost)
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $expenseAccount->id,
        'analytic_account_id' => $analyticAccount->id,
        'debit' => 500, // 500.00 actual cost
        'credit' => 0,
    ]);

    // Run service
    $this->service->updateActuals($budget);

    // Assert
    $budgetLine->refresh();
    expect($budgetLine->actual_amount->getAmount()->toFloat())->toBe(500.00);

    $budget->refresh();
    expect($budget->total_actual->getAmount()->toFloat())->toBe(500.00);
});

it('updates actuals ignores entries outside dates', function () {
    // 1. Setup Analytic Account and Project
    $analyticAccount = AnalyticAccount::factory()->create(['company_id' => $this->company->id]);
    $project = Project::factory()->create([
        'company_id' => $this->company->id,
        'analytic_account_id' => $analyticAccount->id,
    ]);

    // 2. Setup Accounts (Expense)
    $expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Expense,
    ]);

    // 3. Create Budget and Line
    $budget = ProjectBudget::factory()->create([
        'company_id' => $this->company->id,
        'project_id' => $project->id,
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
    ]);

    $budgetLine = ProjectBudgetLine::factory()->create([
        'company_id' => $this->company->id,
        'project_budget_id' => $budget->id,
        'account_id' => $expenseAccount->id,
        'budgeted_amount' => 1000,
    ]);

    // 4. Create Journal Entry OUTSIDE budget period
    $journalEntry = JournalEntry::factory()->create([
        'company_id' => $this->company->id,
        'entry_date' => now()->subYears(2),
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $expenseAccount->id,
        'analytic_account_id' => $analyticAccount->id,
        'debit' => 50000,
        'credit' => 0,
    ]);

    // Run service
    $this->service->updateActuals($budget);

    // Assert
    $budgetLine->refresh();
    expect($budgetLine->actual_amount->getAmount()->toFloat())->toBe(0.00);
});

it('gets budget variance report', function () {
    $project = Project::factory()->create(['company_id' => $this->company->id]);
    $account = Account::factory()->create(['company_id' => $this->company->id, 'name' => 'Test Expenses']);

    // Create Active Budget
    $budget = ProjectBudget::factory()->create([
        'company_id' => $this->company->id,
        'project_id' => $project->id,
        'is_active' => true,
    ]);

    ProjectBudgetLine::factory()->create([
        'company_id' => $this->company->id,
        'project_budget_id' => $budget->id,
        'account_id' => $account->id,
        'budgeted_amount' => 1000, // 1000.00
        'actual_amount' => 500,  // 500.00
    ]);

    $report = $this->service->getBudgetVarianceReport($project);

    expect($report)->toHaveCount(1);
    expect($report[0]['account'])->toBe('Test Expenses');
    expect($report[0]['budgeted'])->toBe(1000.00);
    expect($report[0]['actual'])->toBe(500.00);
    expect($report[0]['variance'])->toBe(500.00); // 1000 - 500
});

it('gets budget utilization percentage', function () {
    $budget = ProjectBudget::factory()->create([
        'company_id' => $this->company->id,
        'total_budget' => 1000, // 1000.00
    ]);

    // Add lines with actuals (since method calculates from lines)
    ProjectBudgetLine::factory()->create([
        'company_id' => $this->company->id,
        'project_budget_id' => $budget->id,
        'budgeted_amount' => 1000,
        'actual_amount' => 500, // 500.00
    ]);

    // Note: getUtilizationPercentage uses budget->total_budget (cached/set on budget) vs sum of lines actuals.
    // total_budget=1000 (1000.00). Actual from line=500 (500.00). 500/1000 = 50%.

    $percent = $this->service->getBudgetUtilizationPercentage($budget);

    expect($percent)->toBe(50.0);
});
