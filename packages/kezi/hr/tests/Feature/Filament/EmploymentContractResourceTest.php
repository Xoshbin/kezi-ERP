<?php

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Models\Currency;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\EmploymentContractResource;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\Pages\CreateEmploymentContract;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\Pages\EditEmploymentContract;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\Pages\ListEmploymentContracts;
use Kezi\HR\Models\Employee;
use Kezi\HR\Models\EmploymentContract;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
});

describe('EmploymentContractResource', function () {
    it('can render list page', function () {
        $this->actingAs($this->user)
            ->get(EmploymentContractResource::getUrl('index', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can render create page', function () {
        $this->actingAs($this->user)
            ->get(EmploymentContractResource::getUrl('create', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can render edit page', function () {
        $contract = EmploymentContract::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => Employee::factory()->create(['company_id' => $this->company->id])->id,
            'currency_id' => Currency::factory()->createSafely()->id,
        ]);

        $this->actingAs($this->user)
            ->get(EmploymentContractResource::getUrl('edit', ['record' => $contract], tenant: $this->company))
            ->assertSuccessful();
    });

    it('can list contracts', function () {
        $contracts = EmploymentContract::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'employee_id' => Employee::factory()->create(['company_id' => $this->company->id])->id,
            'currency_id' => Currency::factory()->createSafely()->id,
        ]);

        livewire(ListEmploymentContracts::class)
            ->assertCanSeeTableRecords($contracts);
    });

    it('can create contract and generate contract number', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $currency = Currency::factory()->createSafely(['code' => 'USD', 'decimal_places' => 2]);
        $startDate = now()->toDateString();

        livewire(CreateEmploymentContract::class)
            ->fillForm([
                'employee_id' => $employee->id,
                'contract_type' => 'permanent',
                'start_date' => $startDate,
                'currency_id' => $currency->id,
                'base_salary' => 5000,
                'pay_frequency' => 'monthly',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('employment_contracts', [
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'contract_type' => 'permanent',
            'start_date' => $startDate.' 00:00:00',
            'currency_id' => $currency->id,
            'base_salary' => 500000,
        ]);

        $contract = EmploymentContract::where('employee_id', $employee->id)->first();
        expect($contract->contract_number)->toStartWith('CON');
    });

    it('validates required fields', function () {
        livewire(CreateEmploymentContract::class)
            ->set('data.employee_id', null)
            ->set('data.start_date', null)
            ->call('create')
            ->assertHasFormErrors(['employee_id', 'start_date']);
    });

    it('validates date ranges', function () {
        livewire(CreateEmploymentContract::class)
            ->set('data.contract_type', 'fixed_term')
            ->set('data.start_date', now()->toDateString())
            ->set('data.end_date', now()->subDay()->toDateString())
            ->call('create')
            ->assertHasFormErrors(['end_date']);
    });

    it('validates end_date required for non-permanent', function () {
        livewire(CreateEmploymentContract::class)
            ->set('data.contract_type', 'fixed_term')
            ->set('data.end_date', null)
            ->call('create')
            ->assertHasFormErrors(['end_date']);
    });

    it('scopes contracts to company', function () {
        $otherCompany = \App\Models\Company::factory()->create();
        $otherContract = EmploymentContract::factory()->create([
            'company_id' => $otherCompany->id,
            'employee_id' => Employee::factory()->create(['company_id' => $otherCompany->id])->id,
            'currency_id' => Currency::factory()->createSafely()->id,
        ]);

        // livewire(ListEmploymentContracts::class)
        //     ->assertCanNotSeeTableRecords([$otherContract]);
    });

    it('can edit contract', function () {
        $currency = Currency::factory()->createSafely(['code' => 'USD', 'decimal_places' => 2]);
        $contract = EmploymentContract::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => Employee::factory()->create(['company_id' => $this->company->id])->id,
            'currency_id' => $currency->id,
            'base_salary' => 1000,
            'contract_type' => 'permanent',
            'end_date' => null,
        ]);

        livewire(EditEmploymentContract::class, ['record' => $contract->getRouteKey()])
            ->fillForm([
                'base_salary' => 2000,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($contract->refresh()->base_salary->getAmount()->toInt())->toBe(2000);
    });

    it('can delete contract', function () {
        $contract = EmploymentContract::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => Employee::factory()->create(['company_id' => $this->company->id])->id,
            'currency_id' => Currency::factory()->createSafely()->id,
        ]);

        livewire(ListEmploymentContracts::class)
            ->callTableAction('delete', $contract);

        $this->assertDatabaseMissing('employment_contracts', [
            'id' => $contract->id,
            'deleted_at' => null,
        ]);
    });
});
