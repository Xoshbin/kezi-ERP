<?php

namespace Modules\Accounting\Tests\Feature\Services\Reports;

use App\DataTransferObjects\Reports\BalanceSheetDTO;
use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\JournalEntryState;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\Reports\BalanceSheetService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

test('it calculates the balance sheet correctly and it balances', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $journal = Journal::factory()->for($this->company)->create();

    // -- Create Accounts --
    // Assets
    $bankAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash]);
    $arAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Receivable]);
    // Liabilities
    $apAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Payable]);
    // Equity
    $equityAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Equity]);
    // P&L Accounts for Current Year Earnings
    $salesAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Income]);
    $rentAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense]);

    $asOfDate = Carbon::parse('2025-03-31');

    // -- Transactions --
    // 1. Initial capital investment (10,000,000 IQD)
    $entry1 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-01-05', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $bankAccount->id, 'debit' => 10000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 10000000]);

    // 2. Sale on account (2,000,000 IQD)
    $entry2 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-02-10', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $arAccount->id, 'debit' => 2000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 2000000]);

    // 3. Purchase on account (500,000 IQD)
    $entry3 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-02-15', 'state' => JournalEntryState::Posted]);
    // For simplicity, we debit bank instead of an inventory asset for this test
    JournalEntryLine::factory()->for($entry3)->create(['account_id' => $bankAccount->id, 'debit' => 500000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry3)->create(['account_id' => $apAccount->id, 'debit' => 0, 'credit' => 500000]);

    // 4. Pay rent (300,000 IQD)
    $entry4 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-03-01', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry4)->create(['account_id' => $rentAccount->id, 'debit' => 300000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry4)->create(['account_id' => $bankAccount->id, 'debit' => 0, 'credit' => 300000]);

    // 5. Ignored future transaction
    JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-04-05', 'state' => JournalEntryState::Posted]);

    // Action
    $service = app(\Modules\Accounting\Services\Reports\BalanceSheetService::class);
    $report = $service->generate($this->company, $asOfDate);

    // Assert
    expect($report)->toBeInstanceOf(BalanceSheetDTO::class);

    // Expected values
    $expectedBank = Money::of('10200000', $currency); // 10M + 500k - 300k
    $expectedAR = Money::of('2000000', $currency);
    $expectedAP = Money::of('500000', $currency);
    $expectedEquity = Money::of('10000000', $currency);
    $expectedEarnings = Money::of('1700000', $currency); // 2M Revenue - 300k Expense

    $expectedTotalAssets = $expectedBank->plus($expectedAR); // 12,200,000
    $expectedTotalLiabilities = $expectedAP; // 500,000
    $expectedTotalEquity = $expectedEquity->plus($expectedEarnings); // 11,700,000

    // Assert Totals
    expect($report->totalAssets)->toEqual($expectedTotalAssets);
    expect($report->totalLiabilities)->toEqual($expectedTotalLiabilities);
    expect($report->currentYearEarnings)->toEqual($expectedEarnings);
    expect($report->retainedEarnings)->toEqual($expectedEquity);
    expect($report->totalEquity)->toEqual($expectedTotalEquity);

    // THE MOST IMPORTANT ASSERTION
    expect($report->totalAssets->isEqualTo($report->totalLiabilitiesAndEquity))->toBeTrue();
    expect($report->totalAssets)->toEqual($expectedTotalLiabilities->plus($expectedTotalEquity));
});

test('it returns empty report when no transactions exist', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $asOfDate = Carbon::parse('2025-03-31');

    // Action
    $service = app(\Modules\Accounting\Services\Reports\BalanceSheetService::class);
    $report = $service->generate($this->company, $asOfDate);

    // Assert
    expect($report)->toBeInstanceOf(BalanceSheetDTO::class);
    expect($report->assetLines)->toHaveCount(0);
    expect($report->liabilityLines)->toHaveCount(0);
    expect($report->equityLines)->toHaveCount(0);
    expect($report->totalAssets)->toEqual(Money::zero($currency));
    expect($report->totalLiabilities)->toEqual(Money::zero($currency));
    expect($report->retainedEarnings)->toEqual(Money::zero($currency));
    expect($report->currentYearEarnings)->toEqual(Money::zero($currency));
    expect($report->totalEquity)->toEqual(Money::zero($currency));
    expect($report->totalLiabilitiesAndEquity)->toEqual(Money::zero($currency));

    // Balance sheet should still balance (zero = zero)
    expect($report->totalAssets->isEqualTo($report->totalLiabilitiesAndEquity))->toBeTrue();
});

test('it handles negative current year earnings correctly', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $journal = Journal::factory()->for($this->company)->create();

    $bankAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash]);
    $equityAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Equity]);
    $salesAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Income]);
    $expenseAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense]);

    $asOfDate = Carbon::parse('2025-03-31');

    // Initial capital
    $entry1 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-01-05', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $bankAccount->id, 'debit' => 1000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 1000000]);

    // Small revenue
    $entry2 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-02-10', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $bankAccount->id, 'debit' => 100000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 100000]);

    // Large expense (creating net loss)
    $entry3 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-03-01', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry3)->create(['account_id' => $expenseAccount->id, 'debit' => 500000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry3)->create(['account_id' => $bankAccount->id, 'debit' => 0, 'credit' => 500000]);

    // Action
    $service = app(\Modules\Accounting\Services\Reports\BalanceSheetService::class);
    $report = $service->generate($this->company, $asOfDate);

    // Assert
    expect($report->currentYearEarnings)->toEqual(Money::of('-400000', $currency)); // 100k Revenue - 500k Expense = -400k Net Loss
    expect($report->totalAssets->isEqualTo($report->totalLiabilitiesAndEquity))->toBeTrue();
});

test('it excludes draft transactions from the report', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $journal = Journal::factory()->for($this->company)->create();

    $bankAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash]);
    $equityAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Equity]);

    $asOfDate = Carbon::parse('2025-03-31');

    // Posted transaction
    $entry1 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-01-05', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $bankAccount->id, 'debit' => 1000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 1000000]);

    // Draft transaction (should be ignored)
    $entry2 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-02-10', 'state' => JournalEntryState::Draft]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $bankAccount->id, 'debit' => 500000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 500000]);

    // Action
    $service = app(\Modules\Accounting\Services\Reports\BalanceSheetService::class);
    $report = $service->generate($this->company, $asOfDate);

    // Assert - Only the posted transaction should be included
    expect($report->totalAssets)->toEqual(Money::of('1000000', $currency));
    expect($report->retainedEarnings)->toEqual(Money::of('1000000', $currency));
    expect($report->totalAssets->isEqualTo($report->totalLiabilitiesAndEquity))->toBeTrue();
});

test('it excludes transactions after the as-of date', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $journal = Journal::factory()->for($this->company)->create();

    $bankAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash]);
    $equityAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Equity]);

    $asOfDate = Carbon::parse('2025-03-31');

    // Transaction before as-of date
    $entry1 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-03-30', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $bankAccount->id, 'debit' => 1000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 1000000]);

    // Transaction after as-of date (should be ignored)
    $entry2 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-04-01', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $bankAccount->id, 'debit' => 500000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 500000]);

    // Action
    $service = app(\Modules\Accounting\Services\Reports\BalanceSheetService::class);
    $report = $service->generate($this->company, $asOfDate);

    // Assert - Only the transaction before as-of date should be included
    expect($report->totalAssets)->toEqual(Money::of('1000000', $currency));
    expect($report->retainedEarnings)->toEqual(Money::of('1000000', $currency));
    expect($report->totalAssets->isEqualTo($report->totalLiabilitiesAndEquity))->toBeTrue();
});

test('it excludes profit and loss accounts from balance sheet accounts', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $journal = Journal::factory()->for($this->company)->create();

    $bankAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash]);
    $salesAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Income]);

    $asOfDate = Carbon::parse('2025-03-31');

    // Transaction with P&L accounts
    $entry1 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-01-05', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $bankAccount->id, 'debit' => 1000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 1000000]);

    // Action
    $service = app(\Modules\Accounting\Services\Reports\BalanceSheetService::class);
    $report = $service->generate($this->company, $asOfDate);

    // Assert - P&L accounts should not appear in balance sheet lines, only in current year earnings
    expect($report->assetLines)->toHaveCount(1); // Only bank account
    expect($report->liabilityLines)->toHaveCount(0);
    expect($report->equityLines)->toHaveCount(0);
    expect($report->currentYearEarnings)->toEqual(Money::of('1000000', $currency)); // Revenue from P&L
    expect($report->totalAssets->isEqualTo($report->totalLiabilitiesAndEquity))->toBeTrue();
});
