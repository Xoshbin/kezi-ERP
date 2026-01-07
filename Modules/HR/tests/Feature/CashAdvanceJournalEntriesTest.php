<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\HR\Enums\CashAdvanceStatus;
use Modules\HR\Models\Employee;
use Modules\HR\Services\HumanResources\CashAdvanceService;

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
        'type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash,
        'currency_id' => $this->company->currency_id,
        'code' => '101000',
    ]);

    $this->expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense,
        'currency_id' => $this->company->currency_id,
        'code' => '601000',
    ]);

    $this->receivableAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Accounting\Enums\Accounting\AccountType::CurrentAssets,
        'currency_id' => $this->company->currency_id,
        'code' => '112000',
    ]);
    $this->company->update(['default_employee_advance_receivable_account_id' => $this->receivableAccount->id]);

    // Journals
    $this->cashJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Accounting\Enums\Accounting\JournalType::Cash,
        'currency_id' => $this->company->currency_id,
        'default_credit_account_id' => $this->bankAccount->id,
        'default_debit_account_id' => $this->bankAccount->id,
    ]);

    $this->generalJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Accounting\Enums\Accounting\JournalType::Miscellaneous,
        'currency_id' => $this->company->currency_id,
    ]);

    $this->service = app(CashAdvanceService::class);
});

test('disbursement creates correct journal entry', function () {
    $amount = Money::of(1000, $this->company->currency->code);

    // Create & Approve
    $dto = new \Modules\HR\DataTransferObjects\HumanResources\CreateCashAdvanceDTO(
        company_id: $this->company->id,
        employee_id: $this->employee->id,
        currency_id: $this->company->currency_id,
        requested_amount: $amount,
        purpose: 'Travel',
        expected_return_date: now()->addWeek()->format('Y-m-d'),
        notes: null
    );
    $advance = $this->service->createAdvance($dto, $this->user);
    $this->service->submitForApproval($advance, $this->user);
    $this->service->approve($advance, $amount, $this->user);

    // Disburse
    $this->service->disburse($advance, $this->bankAccount->id, $this->user);

    $advance->refresh();

    $je = $advance->disbursementJournalEntry;
    expect($je)->not->toBeNull();
    expect($je->lines)->toHaveCount(2);

    // Debit Employee Receivable
    $debitLine = $je->lines->where('account_id', $this->receivableAccount->id)->first();
    expect($debitLine)->not->toBeNull();
    expect($debitLine->debit)->isEqualTo($amount);

    // Credit Bank
    $creditLine = $je->lines->where('account_id', $this->bankAccount->id)->first();
    expect($creditLine)->not->toBeNull();
    expect($creditLine->credit)->isEqualTo($amount);
});

test('settlement with expenses creates expense recognition entry', function () {
    // Setup Disbursed Advance
    $amount = Money::of(1000, $this->company->currency->code);
    $advance = \Modules\HR\Models\CashAdvance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'currency_id' => $this->company->currency_id,
        'requested_amount' => $amount,
        'approved_amount' => $amount,
        'disbursed_amount' => $amount,
        'status' => CashAdvanceStatus::Disbursed,
    ]);

    // Create, Submit, Approve Expense Report for 800
    $expenseAmount = Money::of(800, $this->company->currency->code);
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
                amount: $expenseAmount,
                receipt_reference: 'REC01',
                partner_id: null
            ),
        ],
        notes: null
    );
    $report = $this->service->createExpenseReport($expenseDto, $this->user);
    $this->service->submitExpenseReport($report, $this->user);
    $this->service->approveExpenseReport($report, $this->user);

    // Settle
    $this->service->settle($advance, 'cash_return', $this->bankAccount->id, $this->user);

    $advance->refresh();

    // Check Settlement JE (Expense Recognition)
    $je = $advance->settlementJournalEntry;
    expect($je)->not->toBeNull();

    // Debit Expense
    $expenseLine = $je->lines->where('account_id', $this->expenseAccount->id)->first();
    expect($expenseLine)->not->toBeNull();
    expect($expenseLine->debit)->isEqualTo($expenseAmount);

    // Credit Employee Receivable
    $receivableLine = $je->lines->where('account_id', $this->receivableAccount->id)->first();
    expect($receivableLine)->not->toBeNull();
    expect($receivableLine->credit)->isEqualTo($expenseAmount);
});

test('settlement with cash return creates return entry', function () {
    // 1000 Disbursed, 800 Expense -> 200 Return
    $initialAmount = Money::of(1000, $this->company->currency->code);
    $expenseAmount = Money::of(800, $this->company->currency->code);

    // Helper to setup state
    $advance = \Modules\HR\Models\CashAdvance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'currency_id' => $this->company->currency_id,
        'requested_amount' => $initialAmount,
        'approved_amount' => $initialAmount,
        'disbursed_amount' => $initialAmount,
        'status' => CashAdvanceStatus::Disbursed,
    ]);

    $expenseDto = new \Modules\HR\DataTransferObjects\HumanResources\CreateExpenseReportDTO(
        company_id: $this->company->id,
        cash_advance_id: $advance->id,
        employee_id: $this->employee->id,
        report_date: now()->format('Y-m-d'),
        lines: [
            new \Modules\HR\DataTransferObjects\HumanResources\ExpenseReportLineDTO(
                expense_account_id: $this->expenseAccount->id,
                description: 'Items',
                expense_date: now()->format('Y-m-d'),
                amount: $expenseAmount,
                receipt_reference: null,
                partner_id: null
            ),
        ],
        notes: null
    );
    $report = $this->service->createExpenseReport($expenseDto, $this->user);
    $this->service->submitExpenseReport($report, $this->user);
    $this->service->approveExpenseReport($report, $this->user);

    // Settle with Return
    $this->service->settle($advance, 'cash_return', $this->bankAccount->id, $this->user);

    // Determine the extra journal entry for return
    // It's not stored on the cash advance directly as a single column (we only have settlement_journal_entry_id which is the expense one)
    // But we can search for it by reference or context.

    $returnJe = \Modules\Accounting\Models\JournalEntry::where('reference', "Return {$advance->advance_number}")->first();
    expect($returnJe)->not->toBeNull();

    $balance = $initialAmount->minus($expenseAmount); // 200

    // Dr Bank
    $bankLine = $returnJe->lines->where('account_id', $this->bankAccount->id)->first();
    expect($bankLine->debit)->isEqualTo($balance);

    // Cr Employee Receivable
    $receivableLine = $returnJe->lines->where('account_id', $this->receivableAccount->id)->first();
    expect($receivableLine->credit)->isEqualTo($balance);
});

test('settlement with reimbursement creates reimbursement entry', function () {
    // 1000 Disbursed, 1200 Expense -> 200 Reimbursement (We pay employee)
    $initialAmount = Money::of(1000, $this->company->currency->code);
    $expenseAmount = Money::of(1200, $this->company->currency->code);

    $advance = \Modules\HR\Models\CashAdvance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'currency_id' => $this->company->currency_id,
        'requested_amount' => $initialAmount,
        'approved_amount' => $initialAmount,
        'disbursed_amount' => $initialAmount,
        'status' => CashAdvanceStatus::Disbursed,
    ]);

    $expenseDto = new \Modules\HR\DataTransferObjects\HumanResources\CreateExpenseReportDTO(
        company_id: $this->company->id,
        cash_advance_id: $advance->id,
        employee_id: $this->employee->id,
        report_date: now()->format('Y-m-d'),
        lines: [
            new \Modules\HR\DataTransferObjects\HumanResources\ExpenseReportLineDTO(
                expense_account_id: $this->expenseAccount->id,
                description: 'Expensive Items',
                expense_date: now()->format('Y-m-d'),
                amount: $expenseAmount,
                receipt_reference: null,
                partner_id: null
            ),
        ],
        notes: null
    );
    $report = $this->service->createExpenseReport($expenseDto, $this->user);
    $this->service->submitExpenseReport($report, $this->user);
    $this->service->approveExpenseReport($report, $this->user);

    // Settle with Reimbursement
    $this->service->settle($advance, 'reimbursement', $this->bankAccount->id, $this->user);

    // Find Reimbursement JE
    $reimburseJe = \Modules\Accounting\Models\JournalEntry::where('reference', "Reimbursement {$advance->advance_number}")->first();
    expect($reimburseJe)->not->toBeNull();

    $balance = $expenseAmount->minus($initialAmount); // 200

    // Dr Employee Receivable (clearing the credit balance from expense posting)
    $receivableLine = $reimburseJe->lines->where('account_id', $this->receivableAccount->id)->first();
    expect($receivableLine->debit)->isEqualTo($balance);

    // Cr Bank
    $bankLine = $reimburseJe->lines->where('account_id', $this->bankAccount->id)->first();
    expect($bankLine->credit)->isEqualTo($balance);
});
