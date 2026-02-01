<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\HR\Enums\CashAdvanceStatus;
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

    // Accounts
    $this->bankAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Accounting\Enums\Accounting\AccountType::BankAndCash,
        'currency_id' => $this->company->currency_id,
    ]);

    $this->expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Expense,
        'currency_id' => $this->company->currency_id,
    ]);

    $this->receivableAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Accounting\Enums\Accounting\AccountType::CurrentAssets,
        'currency_id' => $this->company->currency_id,
    ]);
    $this->company->update(['default_employee_advance_receivable_account_id' => $this->receivableAccount->id]);

    // Journals
    $this->generalJournal = \Kezi\Accounting\Models\Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Accounting\Enums\Accounting\JournalType::Miscellaneous,
        'currency_id' => $this->company->currency_id,
    ]);

    $this->service = app(CashAdvanceService::class);
});

test('settles multiple expense reports at once', function () {
    $amount = Money::of(1000, $this->company->currency->code);
    $advance = CashAdvance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'currency_id' => $this->company->currency_id,
        'status' => CashAdvanceStatus::PendingSettlement, // Assuming skipped submission flow for speed
        'disbursed_amount' => $amount,
        'approved_amount' => $amount,
    ]);

    // Report 1: 300
    \Kezi\HR\Models\ExpenseReport::factory()->create([
        'company_id' => $this->company->id,
        'cash_advance_id' => $advance->id,
        'status' => ExpenseReportStatus::Approved,
        'total_amount' => 300, // Should be calculated from lines normally, but here we depend on lines for JE
    ])->lines()->create([
        'company_id' => $this->company->id,
        'expense_account_id' => $this->expenseAccount->id,
        'amount' => 300,
        'expense_date' => now(),
        'description' => 'Exp 1',
    ]);

    // Report 2: 400
    \Kezi\HR\Models\ExpenseReport::factory()->create([
        'company_id' => $this->company->id,
        'cash_advance_id' => $advance->id,
        'status' => ExpenseReportStatus::Approved,
        'total_amount' => 400,
    ])->lines()->create([
        'company_id' => $this->company->id,
        'expense_account_id' => $this->expenseAccount->id,
        'amount' => 400,
        'expense_date' => now(),
        'description' => 'Exp 2',
    ]);

    // Total Expenses: 700. Balance: 300 (Employee Owes).

    $this->service->settle($advance, 'cash_return', $this->bankAccount->id, $this->user);

    $advance->refresh();
    expect($advance->status)->toBe(CashAdvanceStatus::Settled);

    // Check Expense JE Total
    $expenseJe = $advance->settlementJournalEntry;
    expect($expenseJe->lines->sum(fn ($l) => $l->debit->getAmount()->toInt()))->toBe(700);
});

test('settles full return with zero expenses', function () {
    $amount = Money::of(1000, $this->company->currency->code);
    $advance = CashAdvance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'currency_id' => $this->company->currency_id,
        'status' => CashAdvanceStatus::PendingSettlement,
        'disbursed_amount' => $amount,
    ]);

    // No expense reports.

    $this->service->settle($advance, 'cash_return', $this->bankAccount->id, $this->user);

    $advance->refresh();
    expect($advance->status)->toBe(CashAdvanceStatus::Settled);
    expect($advance->settlementJournalEntry)->toBeNull(); // No expenses -> no expense JE

    // Should have a return entry for full 1000
    $returnJe = \Kezi\Accounting\Models\JournalEntry::where('reference', "Return {$advance->advance_number}")->first();
    expect($returnJe)->not->toBeNull();

    $bankLine = $returnJe->lines->where('account_id', $this->bankAccount->id)->first();
    expect($bankLine->debit->getAmount()->toInt())->toBe(1000);
});

test('payroll deduction settlement updates status without cash return entry', function () {
    $amount = Money::of(1000, $this->company->currency->code);
    $advance = CashAdvance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'currency_id' => $this->company->currency_id,
        'status' => CashAdvanceStatus::PendingSettlement,
        'disbursed_amount' => $amount,
    ]);

    // 800 expenses
    \Kezi\HR\Models\ExpenseReport::factory()->create([
        'company_id' => $this->company->id,
        'cash_advance_id' => $advance->id,
        'status' => ExpenseReportStatus::Approved,
        'total_amount' => 800,
    ])->lines()->create([
        'company_id' => $this->company->id,
        'expense_account_id' => $this->expenseAccount->id,
        'amount' => 800,
        'expense_date' => now(),
        'description' => 'Exp 1',
    ]);

    // Balance 200. Payroll Deducation.
    $this->service->settle($advance, 'payroll_deduction', null, $this->user);

    $advance->refresh();
    expect($advance->status)->toBe(CashAdvanceStatus::Settled);
    expect($advance->settlementJournalEntry)->not->toBeNull(); // Expense JE exists

    // BUT no Cash Return JE should exist for this advance (dr bank, cr receivable)
    $returnJe = \Kezi\Accounting\Models\JournalEntry::where('reference', "Return {$advance->advance_number}")->first();
    expect($returnJe)->toBeNull();
});

test('carry forward settlement updates status without reimbursement entry', function () {
    // 1000 Advance, 1200 Expenses. Balance -200 (We owe).
    $amount = Money::of(1000, $this->company->currency->code);
    $advance = CashAdvance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'currency_id' => $this->company->currency_id,
        'status' => CashAdvanceStatus::PendingSettlement,
        'disbursed_amount' => $amount,
    ]);

    \Kezi\HR\Models\ExpenseReport::factory()->create([
        'company_id' => $this->company->id,
        'cash_advance_id' => $advance->id,
        'status' => ExpenseReportStatus::Approved,
        'total_amount' => 1200,
    ])->lines()->create([
        'company_id' => $this->company->id,
        'expense_account_id' => $this->expenseAccount->id,
        'amount' => 1200,
        'expense_date' => now(),
        'description' => 'Exp 1',
    ]);

    // Settlement 'none' (Carry Forward)
    $this->service->settle($advance, 'none', null, $this->user);

    $advance->refresh();
    expect($advance->status)->toBe(CashAdvanceStatus::Settled);

    // No Reimbursement JE (we didn't pay them back now)
    $reimburseJe = \Kezi\Accounting\Models\JournalEntry::where('reference', "Reimbursement {$advance->advance_number}")->first();
    expect($reimburseJe)->toBeNull();
});
