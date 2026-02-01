<?php

namespace Jmeryar\ProjectManagement\Tests\Feature\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\ProjectManagement\Actions\CreateProjectBudgetAction;
use Jmeryar\ProjectManagement\DataTransferObjects\CreateProjectBudgetDTO;
use Jmeryar\ProjectManagement\DataTransferObjects\ProjectBudgetLineDTO;
use Jmeryar\ProjectManagement\Models\Project;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CreateProjectBudgetAction::class);
});

it('creates a project budget with lines successfully', function () {
    $project = Project::factory()->create(['company_id' => $this->company->id]);
    $account1 = Account::factory()->create(['company_id' => $this->company->id]);
    $account2 = Account::factory()->create(['company_id' => $this->company->id]);

    $line1 = new ProjectBudgetLineDTO(
        account_id: $account1->id,
        description: 'Labor Budget',
        budgeted_amount: 500000 // 5000.00
    );

    $line2 = new ProjectBudgetLineDTO(
        account_id: $account2->id,
        description: 'Material Budget',
        budgeted_amount: 300000 // 3000.00
    );

    $dto = new CreateProjectBudgetDTO(
        company_id: $this->company->id,
        project_id: $project->id,
        name: 'FY2026 Budget',
        start_date: now()->startOfYear(),
        end_date: now()->endOfYear(),
        lines: [$line1, $line2]
    );

    $budget = $this->action->execute($dto);
    expect($budget->total_budget->getAmount()->toFloat())->toBe(800.0);
    expect($budget->lines)->toHaveCount(2);

    $this->assertDatabaseHas('project_budgets', [
        'id' => $budget->id,
        'name' => 'FY2026 Budget',
    ]);
});
