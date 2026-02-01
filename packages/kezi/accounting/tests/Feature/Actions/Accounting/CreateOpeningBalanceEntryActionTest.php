<?php

namespace Kezi\Accounting\Tests\Feature\Actions\Accounting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Actions\Accounting\CreateOpeningBalanceEntryAction;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateOpeningBalanceEntryDTO;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Enums\Accounting\JournalEntryState;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\FiscalYear;
use Kezi\Accounting\Models\Journal;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Accounting\Models\JournalEntryLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    // Setup Misc Journal (required for opening entry)
    $this->miscJournal = Journal::factory()->for($this->company)->create(['type' => 'miscellaneous']);

    // Setup typical accounts
    $this->assetAccount = Account::factory()->for($this->company)->create([
        'type' => AccountType::CurrentAssets,
        'code' => '1001',
        'name' => 'Cash',
    ]);

    $this->liabilityAccount = Account::factory()->for($this->company)->create([
        'type' => AccountType::CurrentLiabilities,
        'code' => '2001',
        'name' => 'Loans',
    ]);

    $this->incomeAccount = Account::factory()->for($this->company)->create([
        'type' => AccountType::Income,
        'code' => '4001',
        'name' => 'Sales',
    ]);

    $this->retainedEarningsAccount = Account::factory()->for($this->company)->create([
        'type' => AccountType::Equity,
        'code' => '330101',
        'name' => 'Retained Earnings',
    ]);
});

it('can create opening balance entry from a closed fiscal year', function () {
    // 1. Setup Source Fiscal Year (2023)
    $sourceYear = FiscalYear::factory()
        ->for($this->company)
        ->forYear(2023)
        ->closed()
        ->create();

    // Create some balances in 2023
    $entry = JournalEntry::factory()->for($this->company)->create([
        'entry_date' => '2023-06-15',
        'state' => JournalEntryState::Posted,
        'journal_id' => $this->miscJournal->id,
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'company_id' => $this->company->id,
        'account_id' => $this->assetAccount->id,
        'debit' => 500000, // 5000.00
        'credit' => 0,
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'company_id' => $this->company->id,
        'account_id' => $this->liabilityAccount->id,
        'debit' => 0,
        'credit' => 500000,
    ]);

    // 2. Setup Target Fiscal Year (2024)
    $targetYear = FiscalYear::factory()
        ->for($this->company)
        ->forYear(2024)
        ->open()
        ->create();

    $dto = new CreateOpeningBalanceEntryDTO(
        newFiscalYear: $targetYear,
        previousFiscalYear: $sourceYear,
        createdByUserId: $this->user->id
    );

    $action = app(CreateOpeningBalanceEntryAction::class);
    $openingEntry = $action->execute($dto);

    // Assertions
    expect($openingEntry)->not->toBeNull()
        ->and($openingEntry->entry_date->toDateString())->toBe($targetYear->start_date->toDateString())
        ->and($openingEntry->state)->toBe(JournalEntryState::Draft); // Actions usually create it as Draft for opening

    // Verify Lines
    // Asset should be debited 5000
    $assetLine = $openingEntry->lines()->where('account_id', $this->assetAccount->id)->first();
    expect($assetLine->debit->getAmount()->toInt())->toBe(500000);

    // Liability should be credited 5000
    $liabilityLine = $openingEntry->lines()->where('account_id', $this->liabilityAccount->id)->first();
    expect($liabilityLine->credit->getAmount()->toInt())->toBe(500000);
});

it('can create opening balance entry from an unclosed year with net income adjustment', function () {
    // 1. Setup Source Fiscal Year (2023) - OPEN
    $sourceYear = FiscalYear::factory()
        ->for($this->company)
        ->forYear(2023)
        ->open()
        ->create();

    // Create Balance Sheet transaction
    $entry1 = JournalEntry::factory()->for($this->company)->create([
        'entry_date' => '2023-06-15',
        'state' => JournalEntryState::Posted,
        'journal_id' => $this->miscJournal->id,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry1->id,
        'company_id' => $this->company->id,
        'account_id' => $this->assetAccount->id,
        'debit' => 100000,
        'credit' => 0,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry1->id,
        'company_id' => $this->company->id,
        'account_id' => $this->liabilityAccount->id,
        'debit' => 0,
        'credit' => 100000,
    ]);

    // Create P&L transaction (Profit 400)
    $entry2 = JournalEntry::factory()->for($this->company)->create([
        'entry_date' => '2023-07-15',
        'state' => JournalEntryState::Posted,
        'journal_id' => $this->miscJournal->id,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry2->id,
        'company_id' => $this->company->id,
        'account_id' => $this->assetAccount->id,
        'debit' => 40000,
        'credit' => 0,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry2->id,
        'company_id' => $this->company->id,
        'account_id' => $this->incomeAccount->id,
        'debit' => 0,
        'credit' => 40000,
    ]);

    // Total Assets = 1000 + 400 = 1400.
    // Total Liabilities = 1000.
    // Net Income = 400.
    // Opening Balance for 2024 should be:
    // Debit Asset 1400 (1000 + 400)
    // Credit Liability 1000
    // Credit Retained Earnings 400

    $targetYear = FiscalYear::factory()
        ->for($this->company)
        ->forYear(2024)
        ->open()
        ->create();

    $dto = new CreateOpeningBalanceEntryDTO(
        newFiscalYear: $targetYear,
        previousFiscalYear: $sourceYear,
        createdByUserId: $this->user->id
    );

    $action = app(CreateOpeningBalanceEntryAction::class);
    $openingEntry = $action->execute($dto);

    // Verify Retained Earnings Line (Adjustment line)
    $reLine = $openingEntry->lines()->where('account_id', $this->retainedEarningsAccount->id)->first();
    expect($reLine)->not->toBeNull()
        ->and($reLine->credit->getAmount()->toInt())->toBe(40000);

    // Verify Asset Line
    $assetLine = $openingEntry->lines()->where('account_id', $this->assetAccount->id)->first();
    expect($assetLine->debit->getAmount()->toInt())->toBe(140000);

    // Total Debit must equal Total Credit
    expect($openingEntry->total_debit->getAmount()->toInt())->toBe($openingEntry->total_credit->getAmount()->toInt())
        ->and($openingEntry->total_debit->getAmount()->toInt())->toBe(140000);
});

it('throws exception if no balances found in previous year', function () {
    $sourceYear = FiscalYear::factory()->for($this->company)->forYear(2022)->create();
    $targetYear = FiscalYear::factory()->for($this->company)->forYear(2023)->create();

    $dto = new CreateOpeningBalanceEntryDTO(
        newFiscalYear: $targetYear,
        previousFiscalYear: $sourceYear,
        createdByUserId: $this->user->id
    );

    $action = app(CreateOpeningBalanceEntryAction::class);

    expect(fn () => $action->execute($dto))->toThrow(\RuntimeException::class);
});
