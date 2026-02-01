<?php

namespace Jmeryar\Accounting\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Enums\Budgets\BudgetStatus;
use Jmeryar\Accounting\Enums\Budgets\BudgetType;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Budgets\BudgetResource;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Budgets\Pages\CreateBudget;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Budgets\Pages\EditBudget;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Budgets\Pages\ListBudgets;
use Jmeryar\Accounting\Models\Budget;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
});

describe('BudgetResource', function () {
    it('can render list page', function () {
        $this->actingAs($this->user)
            ->get(BudgetResource::getUrl('index', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can list budgets', function () {
        $budget = Budget::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'TESTING BUDGET LIST',
        ]);

        livewire(ListBudgets::class)
            ->assertCanRenderTableColumn('name')
            ->assertSee('TESTING BUDGET LIST');
    });

    it('can create budget', function () {
        livewire(CreateBudget::class)
            ->fillForm([
                'company_id' => $this->company->id,
                'name' => 'New Year Budget',
                'period_start_date' => now()->startOfYear()->format('Y-m-d'),
                'period_end_date' => now()->endOfYear()->format('Y-m-d'),
                'budget_type' => BudgetType::Financial->value,
                'status' => BudgetStatus::Draft->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('budgets', [
            'company_id' => $this->company->id,
            'name' => 'New Year Budget',
            'budget_type' => BudgetType::Financial->value,
            'status' => BudgetStatus::Draft->value,
        ]);
    });

    it('can edit budget', function () {
        $budget = Budget::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Old Budget Name',
            'status' => BudgetStatus::Draft,
        ]);

        livewire(EditBudget::class, [
            'record' => $budget->getRouteKey(),
        ])
            ->fillForm([
                'name' => 'Updated Budget Name',
                'status' => BudgetStatus::Finalized->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($budget->refresh())
            ->name->toBe('Updated Budget Name')
            ->status->toBe(BudgetStatus::Finalized);
    });

    it('can delete budget', function () {
        $budget = Budget::factory()->create([
            'company_id' => $this->company->id,
        ]);

        livewire(ListBudgets::class)
            ->callTableAction('delete', $budget);

        $this->assertDatabaseMissing('budgets', [
            'id' => $budget->id,
        ]);
    });

    it('can validate required fields', function () {
        livewire(CreateBudget::class)
            ->fillForm([
                'name' => null,
                'period_start_date' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'period_start_date' => 'required',
            ]);
    });
    it('scopes budgets to the active company', function () {
        $budgetInCompany = Budget::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'BUDGET-IN-COMPANY',
        ]);

        $otherCompany = \App\Models\Company::factory()->create();
        $budgetInOtherCompany = Budget::factory()->create([
            'company_id' => $otherCompany->id,
            'name' => 'BUDGET-OUT-COMPANY',
        ]);

        livewire(ListBudgets::class)
            ->searchTable('BUDGET')
            ->assertCanSeeTableRecords([$budgetInCompany])
            ->assertCanNotSeeTableRecords([$budgetInOtherCompany]);
    });
});
