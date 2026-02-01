<?php

namespace Jmeryar\Accounting\Tests\Feature\Actions\Accounting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Jmeryar\Accounting\Actions\Accounting\CloseFiscalYearAction;
use Jmeryar\Accounting\DataTransferObjects\Accounting\CloseFiscalYearDTO;
use Jmeryar\Accounting\Enums\Accounting\AccountType;
use Jmeryar\Accounting\Enums\Accounting\FiscalYearState;
use Jmeryar\Accounting\Enums\Accounting\JournalEntryState;
use Jmeryar\Accounting\Events\FiscalYearClosed;
use Jmeryar\Accounting\Exceptions\FiscalYearNotReadyToCloseException;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\FiscalYear;
use Jmeryar\Accounting\Models\Journal;
use Jmeryar\Accounting\Models\JournalEntry;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    // Setup typical accounts for P&L test
    $this->incomeAccount = Account::factory()->for($this->company)->create([
        'type' => AccountType::Income,
        'code' => '4001',
        'name' => 'Sales',
    ]);

    $this->expenseAccount = Account::factory()->for($this->company)->create([
        'type' => AccountType::Expense,
        'code' => '6001',
        'name' => 'COGS',
    ]);

    $this->retainedEarningsAccount = Account::factory()->for($this->company)->create([
        'type' => AccountType::Equity,
        'code' => '3001',
        'name' => 'Retained Earnings',
    ]);

    // Create a generic journal for transactions
    $this->salesJournal = Journal::factory()->for($this->company)->create(['type' => 'sale']);
    $this->purchaseJournal = Journal::factory()->for($this->company)->create(['type' => 'purchase']);

    // Misc Journal for closing
    $this->miscJournal = Journal::factory()->for($this->company)->create(['type' => 'miscellaneous']);
});

it('can close a fiscal year with profit', function () {
    Event::fake();

    $year = 2024;
    $fiscalYear = FiscalYear::factory()
        ->for($this->company)
        ->forYear($year)
        ->open()
        ->create();

    // 1. Create Income (Credit 1000)
    JournalEntry::factory()
        ->for($this->company)
        ->create([
            'journal_id' => $this->salesJournal->id,
            'entry_date' => $fiscalYear->start_date->addMonth(),
            'state' => JournalEntryState::Posted,
        ])
        ->lines()
        ->createMany([
            // Debit Receivable (Asset) - Irrelevant for closing, but needed for balance
            [
                'company_id' => $this->company->id,
                'account_id' => Account::factory()->for($this->company)->create(['type' => AccountType::CurrentAssets])->id,
                'debit' => 100000, // 1000.00
                'credit' => 0,
            ],
            // Credit Income
            [
                'company_id' => $this->company->id,
                'account_id' => $this->incomeAccount->id,
                'debit' => 0,
                'credit' => 100000,
            ],
        ]);

    // 2. Create Expense (Debit 400)
    JournalEntry::factory()
        ->for($this->company)
        ->create([
            'journal_id' => $this->purchaseJournal->id,
            'entry_date' => $fiscalYear->start_date->addMonth(),
            'state' => JournalEntryState::Posted,
        ])
        ->lines()
        ->createMany([
            // Debit Expense
            [
                'company_id' => $this->company->id,
                'account_id' => $this->expenseAccount->id,
                'debit' => 40000, // 400.00
                'credit' => 0,
            ],
            // Credit Payable (Liability)
            [
                'company_id' => $this->company->id,
                'account_id' => Account::factory()->for($this->company)->create(['type' => AccountType::CurrentLiabilities])->id,
                'debit' => 0,
                'credit' => 40000,
            ],
        ]);

    // Net Profit = 1000 - 400 = 600.
    // Closing Entry should:
    // Debit Income 1000
    // Credit Expense 400
    // Credit Retained Earnings 600

    $dto = new CloseFiscalYearDTO(
        fiscalYear: $fiscalYear,
        retainedEarningsAccountId: $this->retainedEarningsAccount->id,
        closedByUserId: $this->user->id,
        description: 'Closing FY 2024'
    );

    $action = app(CloseFiscalYearAction::class);
    $closedFy = $action->execute($dto);

    // Assertions
    expect($closedFy->state)->toBe(FiscalYearState::Closed)
        ->and($closedFy->closing_journal_entry_id)->not->toBeNull()
        ->and($closedFy->closed_by_user_id)->toBe($this->user->id);

    Event::assertDispatched(FiscalYearClosed::class);

    $closingEntry = JournalEntry::find($closedFy->closing_journal_entry_id);
    expect($closingEntry->state)->toBe(JournalEntryState::Posted);

    // Verify Lines
    // Income Account Line: Should be Debited 1000
    $incomeLine = $closingEntry->lines()->where('account_id', $this->incomeAccount->id)->first();
    expect($incomeLine)->not->toBeNull()
        ->and($incomeLine->debit->getAmount()->toInt())->toBe(100000)
        ->and($incomeLine->credit->isZero())->toBeTrue();

    // Expense Account Line: Should be Credited 400
    $expenseLine = $closingEntry->lines()->where('account_id', $this->expenseAccount->id)->first();
    expect($expenseLine)->not->toBeNull()
        ->and($expenseLine->credit->getAmount()->toInt())->toBe(40000)
        ->and($expenseLine->debit->isZero())->toBeTrue();

    // Retained Earnings Line: Should be Credited 600 (Net Profit)
    $reLine = $closingEntry->lines()->where('account_id', $this->retainedEarningsAccount->id)->first();
    expect($reLine)->not->toBeNull()
        ->and($reLine->credit->getAmount()->toInt())->toBe(60000) // 1000 - 400 = 600
        ->and($reLine->debit->isZero())->toBeTrue();
});

it('can close a fiscal year with loss', function () {
    $year = 2024;
    $fiscalYear = FiscalYear::factory()
        ->for($this->company)
        ->forYear($year)
        ->open()
        ->create();

    // 1. Create Income (Credit 200)
    JournalEntry::factory()
        ->for($this->company)
        ->create([
            'journal_id' => $this->salesJournal->id,
            'entry_date' => $fiscalYear->start_date->addMonth(),
            'state' => JournalEntryState::Posted,
        ])
        ->lines()
        ->createMany([
            [
                'company_id' => $this->company->id,
                'account_id' => Account::factory()->for($this->company)->create(['type' => AccountType::CurrentAssets])->id,
                'debit' => 20000,
                'credit' => 0,
            ],
            [
                'company_id' => $this->company->id,
                'account_id' => $this->incomeAccount->id,
                'debit' => 0,
                'credit' => 20000,
            ],
        ]);

    // 2. Create Expense (Debit 500)
    JournalEntry::factory()
        ->for($this->company)
        ->create([
            'journal_id' => $this->purchaseJournal->id,
            'entry_date' => $fiscalYear->start_date->addMonth(),
            'state' => JournalEntryState::Posted,
        ])
        ->lines()
        ->createMany([
            [
                'company_id' => $this->company->id,
                'account_id' => $this->expenseAccount->id,
                'debit' => 50000,
                'credit' => 0,
            ],
            [
                'company_id' => $this->company->id,
                'account_id' => Account::factory()->for($this->company)->create(['type' => AccountType::CurrentLiabilities])->id,
                'debit' => 0,
                'credit' => 50000,
            ],
        ]);

    // Net Loss = 200 - 500 = -300.
    // Closing Entry should:
    // Debit Income 200
    // Credit Expense 500
    // Debit Retained Earnings 300

    $dto = new CloseFiscalYearDTO(
        fiscalYear: $fiscalYear,
        retainedEarningsAccountId: $this->retainedEarningsAccount->id,
        closedByUserId: $this->user->id,
        description: 'Closing FY 2024 Loss'
    );

    $action = app(CloseFiscalYearAction::class);
    $closedFy = $action->execute($dto);

    $closingEntry = JournalEntry::find($closedFy->closing_journal_entry_id);

    // Retained Earnings Line: Should be Debited 300 (Net Loss)
    $reLine = $closingEntry->lines()->where('account_id', $this->retainedEarningsAccount->id)->first();
    expect($reLine)->not->toBeNull()
        ->and($reLine->debit->getAmount()->toInt())->toBe(30000)
        ->and($reLine->credit->isZero())->toBeTrue();
});

it('cannot close if fiscal year is not open', function () {
    $fiscalYear = FiscalYear::factory()
        ->for($this->company)
        ->closed() // Already closed
        ->create();

    $dto = new CloseFiscalYearDTO(
        fiscalYear: $fiscalYear,
        retainedEarningsAccountId: $this->retainedEarningsAccount->id,
        closedByUserId: $this->user->id,
    );

    $action = app(CloseFiscalYearAction::class);

    expect(fn () => $action->execute($dto))
        ->toThrow(FiscalYearNotReadyToCloseException::class);
});

it('does not create entry if no P&L transactions exist', function () {
    $fiscalYear = FiscalYear::factory()
        ->for($this->company)
        ->forYear(2025)
        ->open()
        ->create();

    $dto = new CloseFiscalYearDTO(
        fiscalYear: $fiscalYear,
        retainedEarningsAccountId: $this->retainedEarningsAccount->id,
        closedByUserId: $this->user->id,
    );

    $action = app(CloseFiscalYearAction::class);
    $closedFy = $action->execute($dto);

    expect($closedFy->state)->toBe(FiscalYearState::Closed)
        ->and($closedFy->closing_journal_entry_id)->toBeNull();
});
