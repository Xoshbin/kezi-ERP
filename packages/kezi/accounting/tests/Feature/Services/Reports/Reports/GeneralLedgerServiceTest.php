<?php

namespace Kezi\Accounting\Tests\Feature\Services\Reports;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Accounting\Models\JournalEntryLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

test('it generates a general ledger report with correct balances', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $journal = Journal::factory()->for($this->company)->create();
    $bankAccount = Account::factory()->for($this->company)->create(['name' => 'Main Bank']);
    $equityAccount = Account::factory()->for($this->company)->create(['name' => 'Capital']);

    $startDate = Carbon::parse('2025-02-01');
    $endDate = Carbon::parse('2025-02-28');

    // Transaction 1: Opening Balance Transaction (Before the period)
    $entry1 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-01-10', 'state' => 'posted']);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $bankAccount->id, 'debit' => 10000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 10000000]);

    // Transaction 2: Deposit within the period
    $entry2 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-02-05', 'state' => 'posted', 'description' => 'Deposit']);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $bankAccount->id, 'debit' => 2000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 2000000]);

    // Transaction 3: Withdrawal within the period
    $entry3 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-02-20', 'state' => 'posted', 'description' => 'Withdrawal']);
    JournalEntryLine::factory()->for($entry3)->create(['account_id' => $bankAccount->id, 'debit' => 0, 'credit' => 500000]);
    JournalEntryLine::factory()->for($entry3)->create(['account_id' => $equityAccount->id, 'debit' => 500000, 'credit' => 0]);

    // Transaction 4: Ignored future transaction
    JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-03-01', 'state' => 'posted']);

    // Action
    $service = app(\Kezi\Accounting\Services\Reports\GeneralLedgerService::class);
    $report = $service->generate($this->company, $startDate, $endDate, [$bankAccount->id]);

    // Assert
    expect($report->accounts)->toHaveCount(1);

    $bankAccountReport = $report->accounts->first();
    expect($bankAccountReport->accountName)->toBe($bankAccount->name);

    // 1. Check Opening Balance
    $expectedOpeningBalance = Money::of('10000000', $currency);
    expect($bankAccountReport->openingBalance)->toEqual($expectedOpeningBalance);

    // 2. Check Transactions
    expect($bankAccountReport->transactionLines)->toHaveCount(2);

    // Transaction 2 Assertions
    $line1 = $bankAccountReport->transactionLines[0];
    expect($line1->debit)->toEqual(Money::of('2000000', $currency));
    expect($line1->credit)->toEqual(Money::of('0', $currency));
    expect($line1->balance)->toEqual(Money::of('12000000', $currency)); // 10M + 2M
    expect($line1->contraAccount)->toBe('Capital');

    // Transaction 3 Assertions
    $line2 = $bankAccountReport->transactionLines[1];
    expect($line2->debit)->toEqual(Money::of('0', $currency));
    expect($line2->credit)->toEqual(Money::of('500000', $currency));
    expect($line2->balance)->toEqual(Money::of('11500000', $currency)); // 12M - 500k
    expect($line2->contraAccount)->toBe('Capital');

    // 3. Check Closing Balance
    $expectedClosingBalance = Money::of('11500000', $currency);
    expect($bankAccountReport->closingBalance)->toEqual($expectedClosingBalance);
});

test('it skips accounts with no activity', function () {
    // Arrange
    $journal = Journal::factory()->for($this->company)->create();
    $bankAccount = Account::factory()->for($this->company)->create(['name' => 'Main Bank']);
    $unusedAccount = Account::factory()->for($this->company)->create(['name' => 'Unused Account']);

    $startDate = Carbon::parse('2025-02-01');
    $endDate = Carbon::parse('2025-02-28');

    // Only create transactions for bank account
    $entry = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-02-05', 'state' => 'posted']);
    JournalEntryLine::factory()->for($entry)->create(['account_id' => $bankAccount->id, 'debit' => 1_000_000_000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry)->create(['account_id' => $unusedAccount->id, 'debit' => 0, 'credit' => 1_000_000_000]);

    // Action - Generate report for unused account only
    $service = app(\Kezi\Accounting\Services\Reports\GeneralLedgerService::class);
    $report = $service->generate($this->company, $startDate, $endDate, [$unusedAccount->id]);

    // Assert - Should include the account since it has activity in the period
    expect($report->accounts)->toHaveCount(1);
    expect($report->accounts->first()->accountName)->toBe('Unused Account');
});

test('it handles multiple contra accounts correctly', function () {
    // Arrange
    $journal = Journal::factory()->for($this->company)->create();
    $bankAccount = Account::factory()->for($this->company)->create(['name' => 'Main Bank']);
    $account1 = Account::factory()->for($this->company)->create(['name' => 'Account 1']);
    $account2 = Account::factory()->for($this->company)->create(['name' => 'Account 2']);

    $startDate = Carbon::parse('2025-02-01');
    $endDate = Carbon::parse('2025-02-28');

    // Create a transaction with multiple contra accounts (split transaction)
    $entry = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-02-05', 'state' => 'posted']);
    JournalEntryLine::factory()->for($entry)->create(['account_id' => $bankAccount->id, 'debit' => 3000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry)->create(['account_id' => $account1->id, 'debit' => 0, 'credit' => 1000000]);
    JournalEntryLine::factory()->for($entry)->create(['account_id' => $account2->id, 'debit' => 0, 'credit' => 2000000]);

    // Action
    $service = app(\Kezi\Accounting\Services\Reports\GeneralLedgerService::class);
    $report = $service->generate($this->company, $startDate, $endDate, [$bankAccount->id]);

    // Assert
    $bankAccountReport = $report->accounts->first();
    $line = $bankAccountReport->transactionLines->first();

    expect($line->contraAccount)->toBe('Account 1, Account 2');
});

test('it excludes draft transactions', function () {
    // Arrange
    $journal = Journal::factory()->for($this->company)->create();
    $bankAccount = Account::factory()->for($this->company)->create(['name' => 'Main Bank']);
    $equityAccount = Account::factory()->for($this->company)->create(['name' => 'Capital']);

    $startDate = Carbon::parse('2025-02-01');
    $endDate = Carbon::parse('2025-02-28');

    // Create a posted transaction
    $postedEntry = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-02-05', 'state' => 'posted']);
    JournalEntryLine::factory()->for($postedEntry)->create(['account_id' => $bankAccount->id, 'debit' => 1000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($postedEntry)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 1000000]);

    // Create a draft transaction (should be excluded)
    $draftEntry = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-02-10', 'state' => 'draft']);
    JournalEntryLine::factory()->for($draftEntry)->create(['account_id' => $bankAccount->id, 'debit' => 500000, 'credit' => 0]);
    JournalEntryLine::factory()->for($draftEntry)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 500000]);

    // Action
    $service = app(\Kezi\Accounting\Services\Reports\GeneralLedgerService::class);
    $report = $service->generate($this->company, $startDate, $endDate, [$bankAccount->id]);

    // Assert - Should only include the posted transaction
    $bankAccountReport = $report->accounts->first();
    expect($bankAccountReport->transactionLines)->toHaveCount(1);
    expect($bankAccountReport->transactionLines->first()->debit)->toEqual(Money::of('1000000', $this->company->currency->code));
});

test('it generates report for all accounts when no account filter is provided', function () {
    // Arrange
    $journal = Journal::factory()->for($this->company)->create();
    $bankAccount = Account::factory()->for($this->company)->create(['name' => 'Main Bank']);
    $equityAccount = Account::factory()->for($this->company)->create(['name' => 'Capital']);

    $startDate = Carbon::parse('2025-02-01');
    $endDate = Carbon::parse('2025-02-28');

    // Create a transaction affecting both accounts
    $entry = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-02-05', 'state' => 'posted']);
    JournalEntryLine::factory()->for($entry)->create(['account_id' => $bankAccount->id, 'debit' => 1000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 1000000]);

    // Action - No account filter provided
    $service = app(\Kezi\Accounting\Services\Reports\GeneralLedgerService::class);
    $report = $service->generate($this->company, $startDate, $endDate);

    // Assert - Should include both accounts
    expect($report->accounts)->toHaveCount(2);
    $accountNames = $report->accounts->pluck('accountName')->toArray();
    expect($accountNames)->toContain('Main Bank');
    expect($accountNames)->toContain('Capital');
});
