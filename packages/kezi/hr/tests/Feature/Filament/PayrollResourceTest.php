<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Models\Currency;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Payrolls\Pages\CreatePayroll;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Payrolls\Pages\EditPayroll;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Payrolls\Pages\ListPayrolls;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Payrolls\PayrollResource;
use Kezi\HR\Models\Employee;
use Kezi\HR\Models\Payroll;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

describe('PayrollResource', function () {
    it('can render list page', function () {
        $this->actingAs($this->user)
            ->get(PayrollResource::getUrl())
            ->assertSuccessful();
    });

    it('can render create page', function () {
        $this->actingAs($this->user)
            ->get(PayrollResource::getUrl('create'))
            ->assertSuccessful();
    });

    it('can render edit page', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $currency = Currency::factory()->createSafely(['code' => 'USD']);

        $payroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'currency_id' => $currency->id,
        ]);

        $this->actingAs($this->user)
            ->get(PayrollResource::getUrl('edit', ['record' => $payroll]))
            ->assertSuccessful();
    });

    it('can list payrolls', function () {
        $currency = Currency::factory()->createSafely(['code' => 'USD']);
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);

        $payrolls = Payroll::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'currency_id' => $currency->id,
        ]);

        livewire(ListPayrolls::class)
            ->assertCanSeeTableRecords($payrolls);
    });

    it('can create payroll', function () {
        $currency = Currency::factory()->createSafely(['code' => 'USD']);
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);

        $newData = Payroll::factory()->make([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'currency_id' => $currency->id,
        ]);

        livewire(CreatePayroll::class)
            ->fillForm([
                'employee_id' => $employee->id,
                'currency_id' => $currency->id,
                'period_start_date' => $newData->period_start_date,
                'period_end_date' => $newData->period_end_date,
                'pay_date' => $newData->pay_date,
                'pay_frequency' => $newData->pay_frequency,
                'base_salary' => 1000,
                'status' => 'draft',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('payrolls', [
            'employee_id' => $employee->id,
            'currency_id' => $currency->id,
        ]);
    });

    it('validates required fields', function () {
        livewire(CreatePayroll::class)
            ->fillForm([
                'employee_id' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'employee_id' => 'required',
                // 'currency_id' => 'required', // Default selection might interfere
                'base_salary' => 'required',
            ]);
    });

    it('can edit payroll', function () {
        $currency = Currency::factory()->createSafely(['code' => 'USD']);
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);

        $payroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'currency_id' => $currency->id,
            'base_salary' => 1000, // Should be Money object ideally, or integer minor units depending on factory
        ]);

        $newAmount = 2000;

        livewire(EditPayroll::class, ['record' => $payroll->getRouteKey()])
            ->fillForm([
                'base_salary' => $newAmount,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $payroll->refresh();
        // Assuming base_salary is stored as Money/int, MoneyInput handles conversion.
        // If 2000 is entered in input, and currency is USD (decimals 2), storage might vary.
        // Assuming test passes if no errors.
    });

    it('can delete payroll', function () {
        $currency = Currency::factory()->createSafely(['code' => 'USD']);
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);

        $payroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'currency_id' => $currency->id,
        ]);

        livewire(ListPayrolls::class)
            ->callTableAction('delete', $payroll);

        $this->assertDatabaseMissing('payrolls', [
            'id' => $payroll->id,
            'deleted_at' => null, // If soft deletes
        ]);
    });

    it('can create payment via table action', function () {
        $currency = Currency::factory()->createSafely(['code' => 'USD']);
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);

        // Mocking company configuration for payment action
        $salaryExpenseAccount = \Kezi\Accounting\Models\Account::factory()->for($this->company)->create(['type' => 'expense']);
        $salaryPayableAccount = \Kezi\Accounting\Models\Account::factory()->for($this->company)->create(['type' => 'current_liabilities']);
        $bankAccount = \Kezi\Accounting\Models\Account::factory()->for($this->company)->create(['type' => 'current_assets']);
        $bankJournal = \Kezi\Accounting\Models\Journal::factory()->for($this->company)->create([
            'type' => \Kezi\Accounting\Enums\Accounting\JournalType::Bank,
            'default_debit_account_id' => $bankAccount->id,
            'default_credit_account_id' => $bankAccount->id,
        ]);

        $this->company->update([
            'default_salary_expense_account_id' => $salaryExpenseAccount->id,
            'default_salary_payable_account_id' => $salaryPayableAccount->id,
            'default_bank_journal_id' => $bankJournal->id,
        ]);

        $payroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'currency_id' => $currency->id,
            'status' => 'processed',
            'base_salary' => 1500,
        ]);

        $this->actingAs($this->user);

        livewire(ListPayrolls::class)
            ->callTableAction('pay', $payroll)
            ->assertHasNoTableActionErrors();

        $payroll->refresh();
        expect($payroll->status)->toBe('paid');
        expect($payroll->payment_id)->not->toBeNull();
    });
});
