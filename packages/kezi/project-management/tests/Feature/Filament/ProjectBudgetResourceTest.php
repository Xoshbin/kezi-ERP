<?php

namespace Kezi\ProjectManagement\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectBudgets\Pages\CreateProjectBudget;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectBudgets\Pages\EditProjectBudget;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectBudgets\ProjectBudgetResource;
use Kezi\ProjectManagement\Models\Project;
use Kezi\ProjectManagement\Models\ProjectBudget;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setCurrentPanel('kezi');
    Filament::setTenant($this->company);
    
    $this->user->update(['current_company_id' => $this->company->id]);
    $this->user->refresh();
    $this->actingAs($this->user);
});

it('can render the list page', function () {
    $this->get(ProjectBudgetResource::getUrl('index'))->assertSuccessful();
});

it('can render the create page', function () {
    $this->get(ProjectBudgetResource::getUrl('create'))->assertSuccessful();
});

it('can create a project budget', function () {
    $project = Project::factory()->create(['company_id' => $this->company->id]);
    $account = Account::factory()->create(['company_id' => $this->company->id]);

    livewire(CreateProjectBudget::class)
        ->fillForm([
            'project_id' => $project->id,
            'name' => 'Q1 Budget',
            'start_date' => now()->startOfQuarter()->format('Y-m-d'),
            'end_date' => now()->endOfQuarter()->format('Y-m-d'),
            'description' => 'Test Budget Description',
            'company_id' => $this->company->id,
        ])
        ->set('data.lines', [
            [
                'account_id' => $this->company->default_bank_account_id,
                'description' => 'Labor Cost',
                'budgeted_amount' => 5000,
                'company_id' => $this->company->id,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $budget = ProjectBudget::latest()->first();
    expect($budget->lines)->toHaveCount(1);

    $this->assertDatabaseHas('project_budget_lines', [
        'project_budget_id' => $budget->id,
        'account_id' => $this->company->default_bank_account_id,
        'budgeted_amount' => 5000000,
    ]);

    // Verify parent budget total was updated by observer
    expect($budget->refresh()->total_budget->getAmount()->toInt())->toBe(5000);
});

it('can render the edit page', function () {
    $budget = ProjectBudget::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->get(ProjectBudgetResource::getUrl('edit', ['record' => $budget]))
        ->assertSuccessful();
});

it('can edit a project budget', function () {
    $budget = ProjectBudget::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Original Name',
    ]);

    livewire(EditProjectBudget::class, [
        'record' => $budget->getRouteKey(),
    ])
        ->fillForm([
            'name' => 'Updated Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($budget->refresh()->name)->toBe('Updated Name');

    $this->assertDatabaseHas('project_budgets', [
        'id' => $budget->id,
        'name' => 'Updated Name',
    ]);
});
