<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\HR\Enums\CashAdvanceStatus;
use Modules\HR\Models\CashAdvance;
use Modules\HR\Models\Employee;
use Modules\HR\Services\HumanResources\CashAdvanceService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->actingAs($this->user);
    $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);

    // Setup required accounts
    $this->bankAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash,
        'currency_id' => $this->company->currency_id,
    ]);

    $this->expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense,
        'currency_id' => $this->company->currency_id,
    ]);

    // Set company setting for employee receivable
    $this->receivableAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Accounting\Enums\Accounting\AccountType::CurrentAssets, // Employee Advances
        'currency_id' => $this->company->currency_id,
    ]);
    $this->company->update(['default_employee_advance_receivable_account_id' => $this->receivableAccount->id]);

    // Setup Cash Journal for disbursement
    $this->cashJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Accounting\Enums\Accounting\JournalType::Cash,
        'currency_id' => $this->company->currency_id,
        'default_credit_account_id' => $this->bankAccount->id,
        'default_debit_account_id' => $this->bankAccount->id,
    ]);

    $this->service = app(CashAdvanceService::class);
});

test('full cash advance lifecycle', function () {
    // 1. Create Request
    $dto = new \Modules\HR\DataTransferObjects\HumanResources\CreateCashAdvanceDTO(
        company_id: $this->company->id,
        employee_id: $this->employee->id,
        currency_id: $this->company->currency_id,
        requested_amount: Money::of(1000, $this->company->currency->code),
        purpose: 'Travel Advance',
        expected_return_date: now()->addWeek()->format('Y-m-d'),
        notes: 'Test notes'
    );

    $advance = $this->service->createAdvance($dto, $this->user);

    expect($advance)
        ->toBeInstanceOf(CashAdvance::class)
        ->status->toBe(CashAdvanceStatus::Draft)
        ->advance_number->not->toBeNull()
        ->requested_amount->isEqualTo(Money::of(1000, $this->company->currency->code))->toBeTrue();

    // 2. Submit
    $this->service->submitForApproval($advance, $this->user);
    expect($advance->fresh()->status)->toBe(CashAdvanceStatus::PendingApproval);

    // 3. Approve
    $approvedAmount = Money::of(1000, $this->company->currency->code);
    $this->service->approve($advance, $approvedAmount, $this->user);
    expect($advance->fresh())
        ->status->toBe(CashAdvanceStatus::Approved)
        ->approved_amount->isEqualTo($approvedAmount)->toBeTrue();

    // 4. Disburse
    $this->service->disburse($advance, $this->bankAccount->id, $this->user);
    expect($advance->fresh())
        ->status->toBe(CashAdvanceStatus::Disbursed)
        ->disbursementJournalEntry->not->toBeNull();

    // 5. Create Expense Report
    $expenseDto = new \Modules\HR\DataTransferObjects\HumanResources\CreateExpenseReportDTO(
        company_id: $this->company->id,
        cash_advance_id: $advance->id,
        employee_id: $this->employee->id,
        report_date: now()->format('Y-m-d'),
        lines: [
            new \Modules\HR\DataTransferObjects\HumanResources\ExpenseReportLineDTO(
                expense_account_id: $this->expenseAccount->id,
                description: 'Hotel',
                expense_date: now()->format('Y-m-d'),
                amount: Money::of(800, $this->company->currency->code),
                receipt_reference: 'REC001',
                partner_id: null
            ),
        ],
        notes: 'Trip expenses'
    );

    $report = $this->service->createExpenseReport($expenseDto, $this->user);
    expect($report->lines)->toHaveCount(1);

    // 6. Submit Expense Report
    $this->service->submitExpenseReport($report, $this->user);
    expect($report->fresh()->status)->toBe(\Modules\HR\Enums\ExpenseReportStatus::Submitted);
    expect($advance->fresh()->status)->toBe(CashAdvanceStatus::PendingSettlement);

    // 7. Approve Expense Report
    $this->service->approveExpenseReport($report, $this->user);
    expect($report->fresh()->status)->toBe(\Modules\HR\Enums\ExpenseReportStatus::Approved);

    // 8. Settle (Employee returns remaining 200)
    $this->service->settle($advance, 'cash_return', $this->bankAccount->id, $this->user);
    expect($advance->fresh())
        ->status->toBe(CashAdvanceStatus::Settled)
        ->settlementJournalEntry->not->toBeNull();
});
