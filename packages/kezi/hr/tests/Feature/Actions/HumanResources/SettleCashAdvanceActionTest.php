<?php

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Enums\Accounting\JournalType;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\HR\Actions\HumanResources\SettleCashAdvanceAction;
use Kezi\HR\Enums\CashAdvanceStatus;
use Kezi\HR\Enums\ExpenseReportStatus;
use Kezi\HR\Models\CashAdvance;
use Kezi\HR\Models\ExpenseReport;
use Kezi\HR\Models\ExpenseReportLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->settlementUser = User::factory()->create();
    $this->action = app(SettleCashAdvanceAction::class);

    // Configure required accounts
    $this->employeeAdvanceReceivableAccount = Account::factory()->for($this->company)->create([
        'name' => 'Employee Advance Receivable',
        'type' => 'current_assets',
    ]);

    $this->bankAccount = Account::factory()->for($this->company)->create([
        'name' => 'Bank Account',
        'type' => 'current_assets',
    ]);

    $this->expenseAccount = Account::factory()->for($this->company)->create([
        'name' => 'Travel Expenses',
        'type' => 'expense',
    ]);

    // Create a miscellaneous journal for expense recognition
    $this->miscJournal = Journal::factory()->for($this->company)->create([
        'type' => 'miscellaneous',
    ]);

    $this->cashJournal = Journal::factory()->for($this->company)->create([
        'type' => JournalType::Bank,
    ]);

    // Set company defaults
    $this->company->update([
        'default_employee_advance_receivable_account_id' => $this->employeeAdvanceReceivableAccount->id,
        'default_cash_journal_id' => $this->cashJournal->id,
    ]);
});

describe('SettleCashAdvanceAction', function () {
    describe('Basic Settlement', function () {
        it('can settle a pending settlement cash advance', function () {
            $cashAdvance = CashAdvance::factory()->create([
                'company_id' => $this->company->id,
                'currency_id' => $this->company->currency_id,
                'status' => CashAdvanceStatus::PendingSettlement,
                'disbursed_amount' => 1000,
            ]);

            $this->action->execute($cashAdvance, 'none', null, $this->settlementUser);

            expect($cashAdvance->refresh())
                ->status->toBe(CashAdvanceStatus::Settled)
                ->settled_at->not->toBeNull();
        });

        it('cannot settle a draft cash advance', function () {
            $cashAdvance = CashAdvance::factory()->create([
                'company_id' => $this->company->id,
                'currency_id' => $this->company->currency_id,
                'status' => CashAdvanceStatus::Draft,
            ]);

            expect(fn () => $this->action->execute($cashAdvance, 'none', null, $this->settlementUser))
                ->toThrow(\InvalidArgumentException::class, 'Only pending settlement cash advances can be settled.');
        });

        it('cannot settle an approved cash advance', function () {
            $cashAdvance = CashAdvance::factory()->create([
                'company_id' => $this->company->id,
                'currency_id' => $this->company->currency_id,
                'status' => CashAdvanceStatus::Approved,
            ]);

            expect(fn () => $this->action->execute($cashAdvance, 'none', null, $this->settlementUser))
                ->toThrow(\InvalidArgumentException::class, 'Only pending settlement cash advances can be settled.');
        });

        it('cannot settle an already settled cash advance', function () {
            $cashAdvance = CashAdvance::factory()->create([
                'company_id' => $this->company->id,
                'currency_id' => $this->company->currency_id,
                'status' => CashAdvanceStatus::Settled,
            ]);

            expect(fn () => $this->action->execute($cashAdvance, 'none', null, $this->settlementUser))
                ->toThrow(\InvalidArgumentException::class, 'Only pending settlement cash advances can be settled.');
        });
    });

    describe('Expense Report Processing', function () {
        it('creates journal entry for approved expense reports', function () {
            $cashAdvance = CashAdvance::factory()->create([
                'company_id' => $this->company->id,
                'currency_id' => $this->company->currency_id,
                'status' => CashAdvanceStatus::PendingSettlement,
                'disbursed_amount' => 1000,
            ]);

            // Create an approved expense report with lines
            $expenseReport = ExpenseReport::factory()->create([
                'company_id' => $this->company->id,
                'cash_advance_id' => $cashAdvance->id,
                'status' => ExpenseReportStatus::Approved,
            ]);

            ExpenseReportLine::factory()->create([
                'company_id' => $this->company->id,
                'expense_report_id' => $expenseReport->id,
                'expense_account_id' => $this->expenseAccount->id,
                'amount' => 500,
            ]);

            $initialJournalEntryCount = JournalEntry::count();

            $this->action->execute($cashAdvance, 'none', null, $this->settlementUser);

            expect(JournalEntry::count())->toBeGreaterThan($initialJournalEntryCount);
            expect($cashAdvance->refresh()->settlement_journal_entry_id)->not->toBeNull();
        });

        it('recognizes expenses correctly in journal entry', function () {
            $cashAdvance = CashAdvance::factory()->create([
                'company_id' => $this->company->id,
                'currency_id' => $this->company->currency_id,
                'status' => CashAdvanceStatus::PendingSettlement,
                'disbursed_amount' => 1000,
            ]);

            $expenseReport = ExpenseReport::factory()->create([
                'company_id' => $this->company->id,
                'cash_advance_id' => $cashAdvance->id,
                'status' => ExpenseReportStatus::Approved,
            ]);

            ExpenseReportLine::factory()->create([
                'company_id' => $this->company->id,
                'expense_report_id' => $expenseReport->id,
                'expense_account_id' => $this->expenseAccount->id,
                'amount' => 600,
            ]);

            $this->action->execute($cashAdvance, 'none', null, $this->settlementUser);

            $cashAdvance->refresh();
            $journalEntry = JournalEntry::find($cashAdvance->settlement_journal_entry_id);

            expect($journalEntry)->not->toBeNull();

            // Check for debit to expense account
            $expenseDebitLine = $journalEntry->lines->firstWhere('account_id', $this->expenseAccount->id);
            expect($expenseDebitLine)->not->toBeNull();
            expect($expenseDebitLine->debit->isEqualTo(Money::of(600, $cashAdvance->currency->code)))->toBeTrue();

            // Check for credit to employee receivable
            $receivableCredit = $journalEntry->lines->firstWhere('account_id', $this->employeeAdvanceReceivableAccount->id);
            expect($receivableCredit)->not->toBeNull();
            expect($receivableCredit->credit->isEqualTo(Money::of(600, $cashAdvance->currency->code)))->toBeTrue();
        });

        it('handles multiple expense report lines', function () {
            $cashAdvance = CashAdvance::factory()->create([
                'company_id' => $this->company->id,
                'currency_id' => $this->company->currency_id,
                'status' => CashAdvanceStatus::PendingSettlement,
                'disbursed_amount' => 1000,
            ]);

            $secondExpenseAccount = Account::factory()->for($this->company)->create([
                'name' => 'Meals Expense',
                'type' => 'expense',
            ]);

            $expenseReport = ExpenseReport::factory()->create([
                'company_id' => $this->company->id,
                'cash_advance_id' => $cashAdvance->id,
                'status' => ExpenseReportStatus::Approved,
            ]);

            ExpenseReportLine::factory()->create([
                'company_id' => $this->company->id,
                'expense_report_id' => $expenseReport->id,
                'expense_account_id' => $this->expenseAccount->id,
                'amount' => 300,
            ]);

            ExpenseReportLine::factory()->create([
                'company_id' => $this->company->id,
                'expense_report_id' => $expenseReport->id,
                'expense_account_id' => $secondExpenseAccount->id,
                'amount' => 200,
            ]);

            $this->action->execute($cashAdvance, 'none', null, $this->settlementUser);

            $cashAdvance->refresh();
            $journalEntry = JournalEntry::find($cashAdvance->settlement_journal_entry_id);

            // Should have lines for both expense accounts plus credit to receivable
            expect($journalEntry->lines->count())->toBeGreaterThanOrEqual(3);
        });

        it('ignores non-approved expense reports', function () {
            $cashAdvance = CashAdvance::factory()->create([
                'company_id' => $this->company->id,
                'currency_id' => $this->company->currency_id,
                'status' => CashAdvanceStatus::PendingSettlement,
                'disbursed_amount' => 1000,
            ]);

            // Draft expense report - should be ignored
            $draftReport = ExpenseReport::factory()->create([
                'company_id' => $this->company->id,
                'cash_advance_id' => $cashAdvance->id,
                'status' => ExpenseReportStatus::Draft,
            ]);

            ExpenseReportLine::factory()->create([
                'company_id' => $this->company->id,
                'expense_report_id' => $draftReport->id,
                'expense_account_id' => $this->expenseAccount->id,
                'amount' => 500,
            ]);

            $this->action->execute($cashAdvance, 'none', null, $this->settlementUser);

            $cashAdvance->refresh();
            // No settlement journal entry since no approved expense reports
            expect($cashAdvance->settlement_journal_entry_id)->toBeNull();
        });
    });

    describe('Cash Return Settlement', function () {
        it('handles cash return when employee owes money', function () {
            $cashAdvance = CashAdvance::factory()->create([
                'company_id' => $this->company->id,
                'currency_id' => $this->company->currency_id,
                'status' => CashAdvanceStatus::PendingSettlement,
                'disbursed_amount' => 1000,
            ]);

            // Expenses less than disbursed amount = employee owes difference
            $expenseReport = ExpenseReport::factory()->create([
                'company_id' => $this->company->id,
                'cash_advance_id' => $cashAdvance->id,
                'status' => ExpenseReportStatus::Approved,
            ]);

            ExpenseReportLine::factory()->create([
                'company_id' => $this->company->id,
                'expense_report_id' => $expenseReport->id,
                'expense_account_id' => $this->expenseAccount->id,
                'amount' => 700,
            ]);

            $this->action->execute($cashAdvance, 'cash_return', $this->bankAccount->id, $this->settlementUser);

            expect($cashAdvance->refresh()->status)->toBe(CashAdvanceStatus::Settled);
        });

        it('requires bank account for cash return', function () {
            $cashAdvance = CashAdvance::factory()->create([
                'company_id' => $this->company->id,
                'currency_id' => $this->company->currency_id,
                'status' => CashAdvanceStatus::PendingSettlement,
                'disbursed_amount' => 1000,
            ]);

            $expenseReport = ExpenseReport::factory()->create([
                'company_id' => $this->company->id,
                'cash_advance_id' => $cashAdvance->id,
                'status' => ExpenseReportStatus::Approved,
            ]);

            ExpenseReportLine::factory()->create([
                'company_id' => $this->company->id,
                'expense_report_id' => $expenseReport->id,
                'expense_account_id' => $this->expenseAccount->id,
                'amount' => 700,
            ]);

            expect(fn () => $this->action->execute($cashAdvance, 'cash_return', null, $this->settlementUser))
                ->toThrow(\InvalidArgumentException::class, 'Bank account required for cash return.');
        });
    });

    describe('Reimbursement Settlement', function () {
        it('handles reimbursement when company owes employee', function () {
            $cashAdvance = CashAdvance::factory()->create([
                'company_id' => $this->company->id,
                'currency_id' => $this->company->currency_id,
                'status' => CashAdvanceStatus::PendingSettlement,
                'disbursed_amount' => 500,
            ]);

            // Expenses more than disbursed = company owes employee
            $expenseReport = ExpenseReport::factory()->create([
                'company_id' => $this->company->id,
                'cash_advance_id' => $cashAdvance->id,
                'status' => ExpenseReportStatus::Approved,
            ]);

            ExpenseReportLine::factory()->create([
                'company_id' => $this->company->id,
                'expense_report_id' => $expenseReport->id,
                'expense_account_id' => $this->expenseAccount->id,
                'amount' => 800,
            ]);

            $this->action->execute($cashAdvance, 'reimbursement', $this->bankAccount->id, $this->settlementUser);

            expect($cashAdvance->refresh()->status)->toBe(CashAdvanceStatus::Settled);
        });

        it('requires bank account for reimbursement', function () {
            $cashAdvance = CashAdvance::factory()->create([
                'company_id' => $this->company->id,
                'currency_id' => $this->company->currency_id,
                'status' => CashAdvanceStatus::PendingSettlement,
                'disbursed_amount' => 500,
            ]);

            $expenseReport = ExpenseReport::factory()->create([
                'company_id' => $this->company->id,
                'cash_advance_id' => $cashAdvance->id,
                'status' => ExpenseReportStatus::Approved,
            ]);

            ExpenseReportLine::factory()->create([
                'company_id' => $this->company->id,
                'expense_report_id' => $expenseReport->id,
                'expense_account_id' => $this->expenseAccount->id,
                'amount' => 800,
            ]);

            expect(fn () => $this->action->execute($cashAdvance, 'reimbursement', null, $this->settlementUser))
                ->toThrow(\InvalidArgumentException::class, 'Bank account required for reimbursement.');
        });
    });

    describe('No Settlement Method', function () {
        it('settles without additional journal entries when no balance adjustment needed', function () {
            $cashAdvance = CashAdvance::factory()->create([
                'company_id' => $this->company->id,
                'currency_id' => $this->company->currency_id,
                'status' => CashAdvanceStatus::PendingSettlement,
                'disbursed_amount' => 1000,
            ]);

            // Expenses exactly match disbursed amount
            $expenseReport = ExpenseReport::factory()->create([
                'company_id' => $this->company->id,
                'cash_advance_id' => $cashAdvance->id,
                'status' => ExpenseReportStatus::Approved,
            ]);

            ExpenseReportLine::factory()->create([
                'company_id' => $this->company->id,
                'expense_report_id' => $expenseReport->id,
                'expense_account_id' => $this->expenseAccount->id,
                'amount' => 1000,
            ]);

            $this->action->execute($cashAdvance, 'none', null, $this->settlementUser);

            expect($cashAdvance->refresh()->status)->toBe(CashAdvanceStatus::Settled);
        });
    });

    describe('Configuration Validation', function () {
        it('throws exception if employee advance receivable account not configured', function () {
            $this->company->update([
                'default_employee_advance_receivable_account_id' => null,
            ]);

            $cashAdvance = CashAdvance::factory()->create([
                'company_id' => $this->company->id,
                'currency_id' => $this->company->currency_id,
                'status' => CashAdvanceStatus::PendingSettlement,
                'disbursed_amount' => 1000,
            ]);

            $expenseReport = ExpenseReport::factory()->create([
                'company_id' => $this->company->id,
                'cash_advance_id' => $cashAdvance->id,
                'status' => ExpenseReportStatus::Approved,
            ]);

            ExpenseReportLine::factory()->create([
                'company_id' => $this->company->id,
                'expense_report_id' => $expenseReport->id,
                'expense_account_id' => $this->expenseAccount->id,
                'amount' => 500,
            ]);

            expect(fn () => $this->action->execute($cashAdvance, 'none', null, $this->settlementUser))
                ->toThrow(\RuntimeException::class, 'Employee advance receivable account not configured for company.');
        });
    });
});
