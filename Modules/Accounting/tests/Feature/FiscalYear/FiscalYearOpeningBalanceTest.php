<?php

namespace Modules\Accounting\Tests\Feature\FiscalYear;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Actions\Accounting\CreateOpeningBalanceEntryAction;
use Modules\Accounting\DataTransferObjects\Accounting\CreateOpeningBalanceEntryDTO;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Enums\Accounting\FiscalYearState;
use Modules\Accounting\Enums\Accounting\JournalEntryState;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;

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
        'debit' => 100000, // 1000.00
        'credit' => 0,
    ]);

    // Liability Cr 400
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $liabilityAccount->id,
        'debit' => 0,
        'credit' => 40000, // 400.00
    ]);

    // Income Cr 600 (To balance entry, just for testing data integrity)
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $incomeAccount->id,
        'debit' => 0,
        'credit' => 60000, // 600.00
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
        'credit' => 60000, // 600.00
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
