<?php

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Models\Currency;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\CashAdvanceResource;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Pages\CreateCashAdvance;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Pages\EditCashAdvance;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Pages\ListCashAdvances;
use Kezi\HR\Models\CashAdvance;
use Kezi\HR\Models\Employee;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
});

describe('CashAdvanceResource', function () {
    it('can render list page', function () {
        $this->actingAs($this->user)
            ->get(CashAdvanceResource::getUrl('index', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can render create page', function () {
        $this->actingAs($this->user)
            ->get(CashAdvanceResource::getUrl('create', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can list cash advances', function () {
        $cashAdvances = CashAdvance::factory()->count(3)->create(['company_id' => $this->company->id]);

        livewire(ListCashAdvances::class)
            ->assertCanSeeTableRecords($cashAdvances);
    });

    it('can create cash advance', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->createSafely(['code' => 'USD']);

        livewire(CreateCashAdvance::class, ['tenant' => $this->company->id])
            ->fillForm([
                'employee_id' => $employee->id,
                'currency_id' => $currency->id,
                'requested_amount' => 500,
                'purpose' => 'Business Trip',
                'expected_return_date' => now()->addDays(30)->toDateString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('cash_advances', [
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'currency_id' => $currency->id,
            'purpose' => 'Business Trip',
        ]);
    });

    it('validates required fields', function () {
        livewire(CreateCashAdvance::class, ['tenant' => $this->company->id])
            ->fillForm([
                'employee_id' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'employee_id' => 'required',
                'currency_id' => 'required',
                'requested_amount' => 'required',
                'purpose' => 'required',
            ]);
    });

    it('can edit cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'purpose' => 'Original Purpose',
        ]);

        livewire(EditCashAdvance::class, ['record' => $cashAdvance->getRouteKey()])
            ->fillForm([
                'purpose' => 'Updated Purpose',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($cashAdvance->refresh()->purpose)->toBe('Updated Purpose');
    });

    it('can delete cash advance via bulk action', function () {
        $cashAdvance = CashAdvance::factory()->create(['company_id' => $this->company->id]);

        livewire(ListCashAdvances::class)
            ->callTableBulkAction('delete', [$cashAdvance]);

        $this->assertDatabaseMissing('cash_advances', [
            'id' => $cashAdvance->id,
        ]);
    });
});
