<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\HR\Enums\ExpenseReportStatus;
use Kezi\HR\Models\CashAdvance;
use Kezi\HR\Models\Employee;
use Kezi\HR\Services\HumanResources\CashAdvanceService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->actingAs($this->user);
    $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);

    $this->expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Expense,
        'currency_id' => $this->company->currency_id,
    ]);

    $this->service = app(CashAdvanceService::class);

    // Create a base cash advance
    $this->advance = CashAdvance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'currency_id' => $this->company->currency_id,
        'status' => \Kezi\HR\Enums\CashAdvanceStatus::Disbursed,
    ]);
});

test('can create expense report with lines and calculates total', function () {
    $amount1 = Money::of(100, $this->company->currency->code);
    $amount2 = Money::of(250, $this->company->currency->code);

    $dto = new \Kezi\HR\DataTransferObjects\HumanResources\CreateExpenseReportDTO(
        company_id: $this->company->id,
        cash_advance_id: $this->advance->id,
        employee_id: $this->employee->id,
        report_date: now()->format('Y-m-d'),
        lines: [
            new \Kezi\HR\DataTransferObjects\HumanResources\ExpenseReportLineDTO(
                expense_account_id: $this->expenseAccount->id,
                description: 'Lunch',
                expense_date: now()->format('Y-m-d'),
                amount: $amount1,
                receipt_reference: 'R1',
                partner_id: null
            ),
            new \Kezi\HR\DataTransferObjects\HumanResources\ExpenseReportLineDTO(
                expense_account_id: $this->expenseAccount->id,
                description: 'Dinner',
                expense_date: now()->format('Y-m-d'),
                amount: $amount2,
                receipt_reference: 'R2',
                partner_id: null
            ),
        ],
        notes: 'Food'
    );

    $report = $this->service->createExpenseReport($dto, $this->user);

    expect($report->lines)->toHaveCount(2);
    expect($report->total_amount)->isEqualTo(Money::of(350, $this->company->currency->code));
    expect($report->status)->toBe(ExpenseReportStatus::Draft);
});

test('cannot create expense line with different currency than advance', function () {
    $otherCurrency = \Kezi\Foundation\Models\Currency::firstWhere('code', 'USD')
        ?? \Kezi\Foundation\Models\Currency::factory()->create(['code' => 'USD']);

    // Advance is in Company Currency (e.g. IQD). Try to add USD line.
    $amountUSD = Money::of(100, 'USD');

    $dto = new \Kezi\HR\DataTransferObjects\HumanResources\CreateExpenseReportDTO(
        company_id: $this->company->id,
        cash_advance_id: $this->advance->id,
        employee_id: $this->employee->id,
        report_date: now()->format('Y-m-d'),
        lines: [
            new \Kezi\HR\DataTransferObjects\HumanResources\ExpenseReportLineDTO(
                expense_account_id: $this->expenseAccount->id,
                description: 'Foreign Lunch',
                expense_date: now()->format('Y-m-d'),
                amount: $amountUSD, // Mismatch
                receipt_reference: 'R1',
                partner_id: null
            ),
        ],
        notes: null
    );

    $this->service->createExpenseReport($dto, $this->user);
})->throws(\InvalidArgumentException::class, 'does not match cash advance currency');

test('submitting expense report updates status', function () {
    $report = \Kezi\HR\Models\ExpenseReport::factory()->create([
        'company_id' => $this->company->id,
        'cash_advance_id' => $this->advance->id,
        'employee_id' => $this->employee->id,
        'status' => ExpenseReportStatus::Draft,
    ]);

    $this->service->submitExpenseReport($report, $this->user);

    expect($report->fresh()->status)->toBe(ExpenseReportStatus::Submitted);
    expect($report->submitted_at)->not->toBeNull();
});

test('approving expense report updates status', function () {
    $report = \Kezi\HR\Models\ExpenseReport::factory()->create([
        'company_id' => $this->company->id,
        'cash_advance_id' => $this->advance->id,
        'employee_id' => $this->employee->id,
        'status' => ExpenseReportStatus::Submitted,
    ]);

    $this->service->approveExpenseReport($report, $this->user);

    expect($report->fresh()->status)->toBe(ExpenseReportStatus::Approved);
    expect($report->approved_at)->not->toBeNull();
    expect($report->approved_by_user_id)->toBe($this->user->id);
});
