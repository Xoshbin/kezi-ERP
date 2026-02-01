<?php

namespace Jmeryar\Accounting\Tests\Feature\FiscalYear;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Actions\Accounting\CreateOpeningBalanceEntryAction;
use Jmeryar\Accounting\DataTransferObjects\Accounting\CreateOpeningBalanceEntryDTO;
use Jmeryar\Accounting\Enums\Accounting\AccountType;
use Jmeryar\Accounting\Enums\Accounting\FiscalYearState;
use Jmeryar\Accounting\Enums\Accounting\JournalEntryState;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\FiscalYear;
use Jmeryar\Accounting\Models\Journal;
use Jmeryar\Accounting\Models\JournalEntry;
use Jmeryar\Accounting\Models\JournalEntryLine;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = \App\Models\User::factory()->create();

    // Setup Journals
    $this->miscellaneousJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'miscellaneous',
        'short_code' => 'MISC',
    ]);
});

it('creates opening balance entry correctly from previous year balances', function () {
    // 1. Setup Previous Year (2024)
    $year2024 = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'name' => '2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'state' => FiscalYearState::Closed,
    ]);

    // 2. Setup Accounts
    $assetAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::CurrentAssets,
        'code' => '101000',
        'name' => 'Cash',
    ]);

    $liabilityAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::CurrentLiabilities,
        'code' => '201000',
        'name' => 'Payables',
    ]);

    $incomeAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::Income, // Should be ignored
        'code' => '400000',
    ]);

    // 3. Create Transactions in 2024
    $entry = JournalEntry::factory()->create([
        'company_id' => $this->company->id,
        'entry_date' => '2024-06-01',
        'state' => JournalEntryState::Posted,
    ]);

    // Asset Dr 1000
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $assetAccount->id,
        'debit' => \Brick\Money\Money::ofMinor(100000, $this->company->currency->code), // 100.000
        'credit' => 0,
    ]);

    // Liability Cr 400
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $liabilityAccount->id,
        'debit' => 0,
        'credit' => \Brick\Money\Money::ofMinor(40000, $this->company->currency->code), // 40.000
    ]);

    // Income Cr 600 (To balance entry, just for testing data integrity)
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $incomeAccount->id,
        'debit' => 0,
        'credit' => \Brick\Money\Money::ofMinor(60000, $this->company->currency->code), // 60.000
    ]);

    // Retained Earnings Account (Equity)
    $retainedEarningsAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::Equity,
        'code' => '300000',
        'name' => 'Retained Earnings',
    ]);

    // Add RE Balance (Simulating the closing of the 600 Income)
    // In a real closed year, Income would be zeroed and 600 moved here.
    // Since we are not running close logic, we just manually add the RE line to "balance" the source data
    // for the purpose of the Opening Entry calculation (which ignores Income but sees RE).
    // Wait, if I leave Income Cr 600 in DB, and add RE Cr 600, total accounting in DB is unbalanced (Dr 1000, Cr 400 + 600 + 600 = 1600).
    // But getOpeningBalanceCandidates only looks at Balance Sheet.
    // BS: Asset Dr 1000. Liability Cr 400. RE Cr 600. -> Balanced.
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $retainedEarningsAccount->id,
        'debit' => 0,
        'credit' => \Brick\Money\Money::ofMinor(60000, $this->company->currency->code), // 60.000
    ]);

    // 4. Setup New Year (2025)
    $year2025 = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'name' => '2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'state' => FiscalYearState::Open,
    ]);

    // 5. Execute Action
    $dto = new CreateOpeningBalanceEntryDTO(
        newFiscalYear: $year2025,
        previousFiscalYear: $year2024,
        createdByUserId: $this->user->id,
    );

    $action = app(CreateOpeningBalanceEntryAction::class);
    $openingEntry = $action->execute($dto);

    // 6. Assertions
    expect($openingEntry)
        ->toBeInstanceOf(JournalEntry::class)
        ->entry_date->format('Y-m-d')->toBe('2025-01-01')
        ->description->toContain('Opening Balance')
        ->state->toBe(JournalEntryState::Draft);

    $lines = $openingEntry->lines;

    // Should contain Asset, Liability, AND Retained Earnings (Equity)
    expect($lines)->toHaveCount(3);

    // Verify Asset Line: Should be Debit 100 (derived from 100000 minor)
    $assetLine = $lines->where('account_id', $assetAccount->id)->first();
    expect($assetLine)
        ->not->toBeNull();
    expect((string) $assetLine->debit->getAmount())->toBe('100.000');
    expect($assetLine->credit->isZero())->toBeTrue();

    // Verify Liability Line: Should be Credit 40
    $liabilityLine = $lines->where('account_id', $liabilityAccount->id)->first();
    expect($liabilityLine)
        ->not->toBeNull();
    expect((string) $liabilityLine->credit->getAmount())->toBe('40.000');
    expect($liabilityLine->debit->isZero())->toBeTrue();

    // Verify Retained Earnings Line: Should be Credit 60
    $reLine = $lines->where('account_id', $retainedEarningsAccount->id)->first();
    expect($reLine)
        ->not->toBeNull();
    expect((string) $reLine->credit->getAmount())->toBe('60.000');
    expect($reLine->debit->isZero())->toBeTrue();
});

it('throws exception if no balance sheet accounts have balance', function () {
    $year2024 = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
    ]);

    $year2025 = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'start_date' => '2025-01-01',
    ]);

    $dto = new CreateOpeningBalanceEntryDTO(
        newFiscalYear: $year2025,
        previousFiscalYear: $year2024,
        createdByUserId: $this->user->id,
    );

    $action = app(CreateOpeningBalanceEntryAction::class);

    expect(fn () => $action->execute($dto))
        ->toThrow(\RuntimeException::class);
});

it('handles unclosed previous year by simulating P&L closure and preserving partner grouping', function () {
    // 1. Setup Open Previous Year (2024)
    $year2024 = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'name' => '2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'state' => FiscalYearState::Open, // Not Closed!
    ]);

    // 2. Setup Accounts
    $receivableAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::Receivable,
        'code' => '120000',
        'name' => 'Account Receivable',
    ]);

    $salesAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::Income,
        'code' => '400000',
    ]);

    $retainedEarningsAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::Equity,
        'code' => '300000',
        'name' => 'Retained Earnings',
    ]);

    // 3. Setup Partners
    $partnerA = \Jmeryar\Foundation\Models\Partner::factory()->create(['company_id' => $this->company->id, 'name' => 'Customer A']);
    $partnerB = \Jmeryar\Foundation\Models\Partner::factory()->create(['company_id' => $this->company->id, 'name' => 'Customer B']);

    // 4. Create Transactions (Sales)
    $entry = JournalEntry::factory()->create([
        'company_id' => $this->company->id,
        'entry_date' => '2024-06-01',
        'state' => JournalEntryState::Posted,
    ]);

    // Sale to Partner A: Dr Receivable 1000, Cr Sales 1000
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $receivableAccount->id,
        'partner_id' => $partnerA->id,
        'debit' => \Brick\Money\Money::ofMinor(100000, $this->company->currency->code), // 100.000
        'credit' => 0,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $salesAccount->id,
        'debit' => 0,
        'credit' => \Brick\Money\Money::ofMinor(100000, $this->company->currency->code), // 100.000
    ]);

    // Sale to Partner B: Dr Receivable 500, Cr Sales 500
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $receivableAccount->id,
        'partner_id' => $partnerB->id,
        'debit' => \Brick\Money\Money::ofMinor(50000, $this->company->currency->code), // 50.000
        'credit' => 0,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $salesAccount->id,
        'debit' => 0,
        'credit' => \Brick\Money\Money::ofMinor(50000, $this->company->currency->code), // 50.000
    ]);

    // Total Income = 1500. Total Expense = 0. Net Income = 1500 (Credit Balance).
    // AR Balance = 1500 (Debit Balance).
    // Opening Entry should have:
    // - AR Partner A: Dr 1000
    // - AR Partner B: Dr 500
    // - Retained Earnings (simulated): Cr 1500 (Profit)

    // 5. Setup New Year (2025)
    $year2025 = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'name' => '2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'state' => FiscalYearState::Open,
    ]);

    // 6. Execute Action
    $dto = new CreateOpeningBalanceEntryDTO(
        newFiscalYear: $year2025,
        previousFiscalYear: $year2024,
        createdByUserId: $this->user->id,
    );

    $action = app(CreateOpeningBalanceEntryAction::class);
    $openingEntry = $action->execute($dto);

    // 7. Assertions
    $lines = $openingEntry->lines;

    // Check Partner A Line
    $lineA = $lines->where('account_id', $receivableAccount->id)->where('partner_id', $partnerA->id)->first();
    expect($lineA)->not->toBeNull();
    expect((string) $lineA->debit->getAmount())->toBe('100.000');

    // Check Partner B Line
    $lineB = $lines->where('account_id', $receivableAccount->id)->where('partner_id', $partnerB->id)->first();
    expect($lineB)->not->toBeNull();
    expect((string) $lineB->debit->getAmount())->toBe('50.000');

    // Check Retained Earnings Line (Simulated Profit)
    $lineRE = $lines->where('account_id', $retainedEarningsAccount->id)->first();
    expect($lineRE)->not->toBeNull();
    // Net Income 1500 Profit => Credit to Equity 1500
    expect((string) $lineRE->credit->getAmount())->toBe('150.000');
    expect($lineRE->description)->toContain('Unallocated Earnings');
});
