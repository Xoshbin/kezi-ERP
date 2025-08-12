<?php

namespace Tests\Feature\Services\Reports;

use App\Enums\Accounting\AccountType;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\Reports\TrialBalanceService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

test('it generates a balanced trial balance report', function () {
    // Arrange
    $company = $this->company;
    $currency = $company->currency->code;
    $journal = Journal::factory()->for($company)->create();
    $asOfDate = Carbon::parse('2025-12-31');

    $bankAccount = Account::factory()->for($company)->create(['type' => AccountType::BankAndCash]);
    $salesAccount = Account::factory()->for($company)->create(['type' => AccountType::Income]);
    $expenseAccount = Account::factory()->for($company)->create(['type' => AccountType::Expense]);

    // Transaction 1: Receive cash for a sale (1,500,000 IQD)
    $entry1 = JournalEntry::factory()->for($company)->for($journal)->create(['entry_date' => '2025-06-10', 'state' => 'posted']);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $bankAccount->id, 'debit' => 1500000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 1500000]);

    // Transaction 2: Pay an expense from the bank (350,000 IQD)
    $entry2 = JournalEntry::factory()->for($company)->for($journal)->create(['entry_date' => '2025-07-15', 'state' => 'posted']);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $expenseAccount->id, 'debit' => 350000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $bankAccount->id, 'debit' => 0, 'credit' => 350000]);

    // Ignored transaction after the "as of" date
    $entry3 = JournalEntry::factory()->for($company)->for($journal)->create(['entry_date' => '2026-01-10', 'state' => 'posted']);
    JournalEntryLine::factory()->for($entry3)->create(['account_id' => $bankAccount->id, 'debit' => 100000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry3)->create(['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 100000]);

    // Action
    $service = app(TrialBalanceService::class);
    $report = $service->generate($company, $asOfDate);

    // Assert
    expect($report->isBalanced)->toBeTrue();

    // Check totals
    $expectedTotal = Money::of('1500000', $currency);
    expect($report->totalDebit)->toEqual($expectedTotal);
    expect($report->totalCredit)->toEqual($expectedTotal);

    // Check individual account lines
    $bankLine = $report->reportLines->firstWhere('accountId', $bankAccount->id);
    $salesLine = $report->reportLines->firstWhere('accountId', $salesAccount->id);
    $expenseLine = $report->reportLines->firstWhere('accountId', $expenseAccount->id);

    // Bank Account: 1.5M Debit - 350k Credit = 1.15M Debit Balance
    expect($bankLine->debit)->toEqual(Money::of('1150000', $currency));
    expect($bankLine->credit)->toEqual(Money::zero($currency));

    // Sales Account: 1.5M Credit Balance
    expect($salesLine->debit)->toEqual(Money::zero($currency));
    expect($salesLine->credit)->toEqual(Money::of('1500000', $currency));

    // Expense Account: 350k Debit Balance
    expect($expenseLine->debit)->toEqual(Money::of('350000', $currency));
    expect($expenseLine->credit)->toEqual(Money::zero($currency));

    // Verify final totals again with calculated balances
    $finalDebitTotal = $bankLine->debit->plus($expenseLine->debit);
    $finalCreditTotal = $salesLine->credit;
    expect($finalDebitTotal)->toEqual($finalCreditTotal);
});

test('it excludes draft journal entries from trial balance', function () {
    // Arrange
    $company = $this->company;
    $journal = Journal::factory()->for($company)->create();
    $asOfDate = Carbon::parse('2025-12-31');

    $bankAccount = Account::factory()->for($company)->create(['type' => AccountType::BankAndCash]);
    $salesAccount = Account::factory()->for($company)->create(['type' => AccountType::Income]);

    // Draft transaction (should be excluded)
    $draftEntry = JournalEntry::factory()->for($company)->for($journal)->create(['entry_date' => '2025-06-10', 'state' => 'draft']);
    JournalEntryLine::factory()->for($draftEntry)->create(['account_id' => $bankAccount->id, 'debit' => 1000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($draftEntry)->create(['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 1000000]);

    // Action
    $service = app(TrialBalanceService::class);
    $report = $service->generate($company, $asOfDate);

    // Assert
    expect($report->reportLines)->toHaveCount(0);
    expect($report->totalDebit)->toEqual(Money::zero($company->currency->code));
    expect($report->totalCredit)->toEqual(Money::zero($company->currency->code));
    expect($report->isBalanced)->toBeTrue();
});

test('it excludes accounts with zero balances', function () {
    // Arrange
    $company = $this->company;
    $journal = Journal::factory()->for($company)->create();
    $asOfDate = Carbon::parse('2025-12-31');

    $bankAccount = Account::factory()->for($company)->create(['type' => AccountType::BankAndCash]);
    $salesAccount = Account::factory()->for($company)->create(['type' => AccountType::Income]);

    // Transaction that nets to zero
    $entry1 = JournalEntry::factory()->for($company)->for($journal)->create(['entry_date' => '2025-06-10', 'state' => 'posted']);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $bankAccount->id, 'debit' => 1000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 1000000]);

    // Reverse transaction
    $entry2 = JournalEntry::factory()->for($company)->for($journal)->create(['entry_date' => '2025-06-15', 'state' => 'posted']);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $bankAccount->id, 'debit' => 0, 'credit' => 1000000]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $salesAccount->id, 'debit' => 1000000, 'credit' => 0]);

    // Action
    $service = app(TrialBalanceService::class);
    $report = $service->generate($company, $asOfDate);

    // Assert
    expect($report->reportLines)->toHaveCount(0);
    expect($report->totalDebit)->toEqual(Money::zero($company->currency->code));
    expect($report->totalCredit)->toEqual(Money::zero($company->currency->code));
    expect($report->isBalanced)->toBeTrue();
});

test('it orders accounts by account code', function () {
    // Arrange
    $company = $this->company;
    $journal = Journal::factory()->for($company)->create();
    $asOfDate = Carbon::parse('2025-12-31');

    $account1 = Account::factory()->for($company)->create(['code' => '1000', 'type' => AccountType::BankAndCash]);
    $account2 = Account::factory()->for($company)->create(['code' => '4000', 'type' => AccountType::Income]);
    $account3 = Account::factory()->for($company)->create(['code' => '2000', 'type' => AccountType::Expense]);

    // Create transactions for each account
    $entry = JournalEntry::factory()->for($company)->for($journal)->create(['entry_date' => '2025-06-10', 'state' => 'posted']);
    JournalEntryLine::factory()->for($entry)->create(['account_id' => $account1->id, 'debit' => 1000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry)->create(['account_id' => $account2->id, 'debit' => 0, 'credit' => 1000000]);

    // Create a second entry that zeros out account3
    $entry2 = JournalEntry::factory()->for($company)->for($journal)->create(['entry_date' => '2025-06-11', 'state' => 'posted']);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $account3->id, 'debit' => 500000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $account3->id, 'debit' => 0, 'credit' => 500000]);

    // Action
    $service = app(TrialBalanceService::class);
    $report = $service->generate($company, $asOfDate);

    // Assert
    expect($report->reportLines)->toHaveCount(2); // Only accounts with non-zero balances
    expect($report->reportLines->first()->accountCode)->toBe('1000');
    expect($report->reportLines->last()->accountCode)->toBe('4000');
});
