<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\HR\Enums\CashAdvanceStatus;
use Kezi\HR\Enums\ExpenseReportStatus;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Pages\CreateExpenseReport;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Pages\ListExpenseReports;
use Kezi\HR\Models\CashAdvance;
use Kezi\HR\Models\Employee;
use Kezi\HR\Models\ExpenseReport;
use Livewire\Livewire;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);

    $this->expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Expense,
        'currency_id' => $this->company->currency_id,
        'name' => 'Travel Expense',
    ]);
});

test('can list expense reports', function () {
    $advance = CashAdvance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'status' => CashAdvanceStatus::Disbursed,
    ]);

    $report = ExpenseReport::factory()->create([
        'company_id' => $this->company->id,
        'cash_advance_id' => $advance->id,
        'employee_id' => $this->employee->id,
        'report_number' => 'EXP-001',
    ]);

    Livewire::test(ListExpenseReports::class)
        ->assertCanSeeTableRecords([$report])
        ->assertSee('EXP-001');
});

test('can create expense report', function () {
    $advance = CashAdvance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'currency_id' => $this->company->currency_id,
        'status' => CashAdvanceStatus::Disbursed,
    ]);

    Livewire::test(CreateExpenseReport::class)
        ->fillForm([
            'cash_advance_id' => $advance->id,
            'report_date' => now()->format('Y-m-d'),
            'lines' => [
                [
                    'expense_account_id' => $this->expenseAccount->id,
                    'description' => 'Taxi',
                    'expense_date' => now()->format('Y-m-d'),
                    'amount' => '50',
                ],
            ],
            'notes' => 'Test Report',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $report = ExpenseReport::first();
    expect($report)
        ->cash_advance_id->toBe($advance->id)
        ->employee_id->toBe($this->employee->id)
        ->total_amount->isEqualTo(Money::of(50, $this->company->currency->code))->toBeTrue()
        ->status->toBe(ExpenseReportStatus::Draft);

    expect($report->lines)->toHaveCount(1);
    expect($report->lines->first()->amount->getAmount()->toInt())->toBe(50);
});

test('can submit expense report via table action', function () {
    $advance = CashAdvance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'status' => CashAdvanceStatus::Disbursed,
    ]);

    $report = ExpenseReport::factory()->create([
        'company_id' => $this->company->id,
        'cash_advance_id' => $advance->id,
        'employee_id' => $this->employee->id,
        'status' => ExpenseReportStatus::Draft,
    ]);

    Livewire::test(ListExpenseReports::class)
        ->callTableAction('submit', $report);

    expect($report->fresh()->status)->toBe(ExpenseReportStatus::Submitted);
    expect($advance->fresh()->status)->toBe(CashAdvanceStatus::PendingSettlement);
});

test('can approve expense report via table action', function () {
    $advance = CashAdvance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'status' => CashAdvanceStatus::PendingSettlement,
    ]);

    $report = ExpenseReport::factory()->create([
        'company_id' => $this->company->id,
        'cash_advance_id' => $advance->id,
        'employee_id' => $this->employee->id,
        'status' => ExpenseReportStatus::Submitted,
    ]);

    Livewire::test(ListExpenseReports::class)
        ->callTableAction('approve', $report);

    expect($report->fresh()->status)->toBe(ExpenseReportStatus::Approved);
});
