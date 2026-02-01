<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\HR\Enums\CashAdvanceStatus;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Pages\CreateCashAdvance;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Pages\ListCashAdvances;
use Jmeryar\HR\Models\CashAdvance;
use Jmeryar\HR\Models\Employee;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);

    // Accounts
    $receivableAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::Receivable,
        'currency_id' => $this->company->currency_id,
        'name' => 'Employee Advance Receivable',
    ]);
    $this->company->update(['default_employee_advance_receivable_account_id' => $receivableAccount->id]);

    $this->bankAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::BankAndCash,
        'currency_id' => $this->company->currency_id,
        'name' => 'Bank ABC',
    ]);
});

test('can list cash advances', function () {
    $advance = CashAdvance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'advance_number' => 'ADV-001',
    ]);

    Livewire::test(ListCashAdvances::class)
        ->assertCanSeeTableRecords([$advance])
        ->assertSee('ADV-001');
});

test('can create cash advance', function () {
    Livewire::test(CreateCashAdvance::class)
        ->fillForm([
            'employee_id' => $this->employee->id,
            'requested_amount' => '500',
            'currency_id' => $this->company->currency_id,
            'purpose' => 'Test Advance',
            'expected_return_date' => now()->addWeek()->format('Y-m-d'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(CashAdvance::first())
        ->employee_id->toBe($this->employee->id)
        ->requested_amount->isEqualTo(Money::of(500, $this->company->currency->code))->toBeTrue()
        ->status->toBe(CashAdvanceStatus::Draft);
});

test('can submit cash advance via table action', function () {
    $advance = CashAdvance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'status' => CashAdvanceStatus::Draft,
    ]);

    Livewire::test(ListCashAdvances::class)
        ->callTableAction('submit', $advance);

    expect($advance->fresh()->status)->toBe(CashAdvanceStatus::PendingApproval);
});

test('can approve cash advance via table action', function () {
    $advance = CashAdvance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'currency_id' => $this->company->currency_id,
        'status' => CashAdvanceStatus::PendingApproval,
        'requested_amount' => 1000,
    ]);

    Livewire::test(ListCashAdvances::class)
        ->callTableAction('approve', $advance, data: [
            'approved_amount' => '1000',
        ])
        ->assertHasNoTableActionErrors();

    expect($advance->fresh()->status)->toBe(CashAdvanceStatus::Approved);
    expect($advance->fresh()->approved_amount->getAmount()->toInt())->toBe(1000);
});

test('can disburse cash advance via table action', function () {
    $advance = CashAdvance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'currency_id' => $this->company->currency_id,
        'status' => CashAdvanceStatus::Approved,
        'approved_amount' => 1000,
    ]);

    Livewire::test(ListCashAdvances::class)
        ->callTableAction('disburse', $advance, data: [
            'bank_account_id' => $this->bankAccount->id,
        ])
        ->assertHasNoTableActionErrors();

    expect($advance->fresh()->status)->toBe(CashAdvanceStatus::Disbursed);
});
