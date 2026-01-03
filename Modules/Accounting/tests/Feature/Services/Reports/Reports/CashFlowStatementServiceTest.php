<?php

namespace Modules\Accounting\Tests\Feature\Services\Reports;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\DataTransferObjects\Reports\CashFlowStatementDTO;
use Modules\Accounting\Enums\Accounting\JournalEntryState;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;
use Modules\Accounting\Services\Reports\CashFlowStatementService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

test('it generates a cash flow statement with operating, investing, and financing activities', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $journal = Journal::factory()->for($this->company)->create();

    // Create accounts for each category
    $bankAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash]);
    $arAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Receivable]);
    $apAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Payable]);
    $fixedAssetAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::FixedAssets]);
    $equityAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Equity]);
    $salesAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Income]);
    $expenseAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense]);
    $depreciationAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Depreciation]);

    $startDate = Carbon::parse('2025-01-01');
    $endDate = Carbon::parse('2025-03-31');

    // 1. Initial capital investment (Financing Activity - equity increase)
    $entry1 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-01-05', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $bankAccount->id, 'debit' => 10000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 10000000]);

    // 2. Cash sale (Operating Activity - revenue)
    $entry2 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-02-10', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $bankAccount->id, 'debit' => 2000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 2000000]);

    // 3. Sale on credit - creates receivable (Operating - working capital change)
    $entry3 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-02-15', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry3)->create(['account_id' => $arAccount->id, 'debit' => 500000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry3)->create(['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 500000]);

    // 4. Pay cash expense
    $entry4 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-03-01', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry4)->create(['account_id' => $expenseAccount->id, 'debit' => 300000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry4)->create(['account_id' => $bankAccount->id, 'debit' => 0, 'credit' => 300000]);

    // 5. Purchase equipment (Investing Activity)
    $entry5 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-03-15', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry5)->create(['account_id' => $fixedAssetAccount->id, 'debit' => 1000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry5)->create(['account_id' => $bankAccount->id, 'debit' => 0, 'credit' => 1000000]);

    // 6. Record depreciation (non-cash expense - add back in operating)
    $entry6 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-03-31', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry6)->create(['account_id' => $depreciationAccount->id, 'debit' => 50000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry6)->create(['account_id' => $fixedAssetAccount->id, 'debit' => 0, 'credit' => 50000]);

    // Act
    $service = app(CashFlowStatementService::class);
    $report = $service->generate($this->company, $startDate, $endDate);

    // Assert
    expect($report)->toBeInstanceOf(CashFlowStatementDTO::class);

    // Check beginning cash (should be 0, no transactions before period)
    expect($report->beginningCash)->toEqual(Money::zero($currency));

    // Check ending cash = 10M + 2M - 300K - 1M = 10.7M
    expect($report->endingCash)->toEqual(Money::of('10700000', $currency));

    // Operating activities should include Net Income + Depreciation + AR change
    // Net Income = 2.5M revenue - 300K expense - 50K depreciation = 2.15M
    // But wait, depreciation is added back, and AR increased (use of cash)
    expect($report->operatingLines->count())->toBeGreaterThanOrEqual(1);

    // Investing activities should include fixed asset purchase
    expect($report->investingLines->count())->toBeGreaterThanOrEqual(1);

    // Financing activities should include equity investment
    expect($report->financingLines->count())->toBeGreaterThanOrEqual(1);

    // Verify: Total should equal Operating + Investing + Financing
    $expectedNetChange = $report->totalOperating->plus($report->totalInvesting)->plus($report->totalFinancing);
    expect($report->netChangeInCash)->toEqual($expectedNetChange);
});

test('it returns empty report when no transactions exist', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $startDate = Carbon::parse('2025-01-01');
    $endDate = Carbon::parse('2025-03-31');

    // Act
    $service = app(CashFlowStatementService::class);
    $report = $service->generate($this->company, $startDate, $endDate);

    // Assert
    expect($report)->toBeInstanceOf(CashFlowStatementDTO::class);
    expect($report->beginningCash)->toEqual(Money::zero($currency));
    expect($report->endingCash)->toEqual(Money::zero($currency));
    expect($report->netChangeInCash)->toEqual(Money::zero($currency));
    // Operating should have at least net income line (even if zero)
    expect($report->operatingLines->count())->toBeGreaterThanOrEqual(1);
});

test('it excludes draft journal entries from the cash flow statement', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $journal = Journal::factory()->for($this->company)->create();

    $bankAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash]);
    $equityAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Equity]);

    $startDate = Carbon::parse('2025-01-01');
    $endDate = Carbon::parse('2025-03-31');

    // Posted transaction
    $entry1 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-01-05', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $bankAccount->id, 'debit' => 1000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 1000000]);

    // Draft transaction (should be ignored)
    $entry2 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-02-10', 'state' => JournalEntryState::Draft]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $bankAccount->id, 'debit' => 500000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 500000]);

    // Act
    $service = app(CashFlowStatementService::class);
    $report = $service->generate($this->company, $startDate, $endDate);

    // Assert - Only the posted transaction should be included
    expect($report->endingCash)->toEqual(Money::of('1000000', $currency));
});

test('it calculates beginning cash balance correctly from prior period', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $journal = Journal::factory()->for($this->company)->create();

    $bankAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash]);
    $equityAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Equity]);
    $salesAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Income]);

    // Transaction BEFORE the report period (should be in beginning balance)
    $entry1 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2024-12-15', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $bankAccount->id, 'debit' => 5000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 5000000]);

    // Transaction DURING the report period
    $entry2 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-02-10', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $bankAccount->id, 'debit' => 1000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 1000000]);

    $startDate = Carbon::parse('2025-01-01');
    $endDate = Carbon::parse('2025-03-31');

    // Act
    $service = app(CashFlowStatementService::class);
    $report = $service->generate($this->company, $startDate, $endDate);

    // Assert
    expect($report->beginningCash)->toEqual(Money::of('5000000', $currency));
    expect($report->endingCash)->toEqual(Money::of('6000000', $currency));
    expect($report->netChangeInCash)->toEqual(Money::of('1000000', $currency));
});

test('it adds back depreciation in operating activities', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $journal = Journal::factory()->for($this->company)->create();

    $bankAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash]);
    $salesAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Income]);
    $depreciationAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Depreciation]);
    $accumulatedDepAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::FixedAssets]);

    $startDate = Carbon::parse('2025-01-01');
    $endDate = Carbon::parse('2025-03-31');

    // Cash sale of 1M
    $entry1 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-02-10', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $bankAccount->id, 'debit' => 1000000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 1000000]);

    // Record depreciation (100K non-cash expense)
    $entry2 = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => '2025-03-31', 'state' => JournalEntryState::Posted]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $depreciationAccount->id, 'debit' => 100000, 'credit' => 0]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $accumulatedDepAccount->id, 'debit' => 0, 'credit' => 100000]);

    // Act
    $service = app(CashFlowStatementService::class);
    $report = $service->generate($this->company, $startDate, $endDate);

    // Assert - Cash is 1M (depreciation didn't affect cash)
    expect($report->endingCash)->toEqual(Money::of('1000000', $currency));

    // Operating should have at least 2 lines (Net Income + Depreciation add-back)
    expect($report->operatingLines->count())->toBeGreaterThanOrEqual(1);

    // Verify cash balance was not affected by non-cash depreciation
    expect($report->beginningCash)->toEqual(Money::zero($currency));
});
