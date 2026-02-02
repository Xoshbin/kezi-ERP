<?php

namespace Kezi\Accounting\Tests\Feature\FiscalYear;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Actions\Accounting\CloseFiscalYearAction;
use Kezi\Accounting\Actions\Accounting\CreateFiscalYearAction;
use Kezi\Accounting\Actions\Accounting\ReopenFiscalYearAction;
use Kezi\Accounting\DataTransferObjects\Accounting\CloseFiscalYearDTO;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateFiscalYearDTO;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Enums\Accounting\FiscalYearState;
use Kezi\Accounting\Exceptions\FiscalYearCannotBeReopenedException;
use Kezi\Accounting\Exceptions\FiscalYearNotReadyToCloseException;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\FiscalYear;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Accounting\Models\JournalEntryLine;
use Kezi\Accounting\Services\FiscalYearService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->fiscalYearService = app(FiscalYearService::class);
    $this->createFiscalYearAction = app(CreateFiscalYearAction::class);
    $this->closeFiscalYearAction = app(CloseFiscalYearAction::class);
    $this->reopenFiscalYearAction = app(ReopenFiscalYearAction::class);
});

it('creates a fiscal year', function () {
    $dto = new CreateFiscalYearDTO(
        companyId: $this->company->id,
        name: 'FY 2025',
        startDate: \Carbon\Carbon::create(2025, 1, 1),
        endDate: \Carbon\Carbon::create(2025, 12, 31),
        generatePeriods: false,
    );

    $fiscalYear = $this->createFiscalYearAction->execute($dto);

    expect($fiscalYear)
        ->toBeInstanceOf(FiscalYear::class)
        ->name->toBe('FY 2025')
        ->state->toBe(FiscalYearState::Open)
        ->company_id->toBe($this->company->id);
});

it('generates monthly periods when requested', function () {
    $dto = new CreateFiscalYearDTO(
        companyId: $this->company->id,
        name: 'FY 2025',
        startDate: \Carbon\Carbon::create(2025, 1, 1),
        endDate: \Carbon\Carbon::create(2025, 12, 31),
        generatePeriods: true,
    );

    $fiscalYear = $this->createFiscalYearAction->execute($dto);

    expect($fiscalYear->periods)->toHaveCount(12);
    expect($fiscalYear->periods->first()->name)->toBe('January 2025');
    expect($fiscalYear->periods->last()->name)->toBe('December 2025');
});

it('prevents overlapping fiscal years', function () {
    // Create first fiscal year
    $dto1 = new CreateFiscalYearDTO(
        companyId: $this->company->id,
        name: 'FY 2025',
        startDate: \Carbon\Carbon::create(2025, 1, 1),
        endDate: \Carbon\Carbon::create(2025, 12, 31),
        generatePeriods: false,
    );
    $this->createFiscalYearAction->execute($dto1);

    // Try to create overlapping year
    $dto2 = new CreateFiscalYearDTO(
        companyId: $this->company->id,
        name: 'FY 2025 Q3-Q4',
        startDate: \Carbon\Carbon::create(2025, 7, 1),
        endDate: \Carbon\Carbon::create(2026, 6, 30),
        generatePeriods: false,
    );

    expect(fn () => $this->createFiscalYearAction->execute($dto2))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('calculates P&L balances correctly for closing', function () {
    // Create fiscal year
    $fiscalYear = FiscalYear::factory()
        ->for($this->company)
        ->forYear(2025)
        ->open()
        ->create();

    // Create income and expense accounts
    $incomeAccount = Account::factory()
        ->for($this->company)
        ->create(['type' => AccountType::Income]);

    $expenseAccount = Account::factory()
        ->for($this->company)
        ->create(['type' => AccountType::Expense]);

    // Create journal entry with income (credit balance)
    $journalEntry = JournalEntry::factory()
        ->for($this->company)
        ->create([
            'entry_date' => \Carbon\Carbon::create(2025, 6, 15),
            'state' => 'posted',
        ]);

    JournalEntryLine::factory()
        ->for($journalEntry)
        ->for($this->company)
        ->create([
            'account_id' => $incomeAccount->id,
            'debit' => Money::zero($this->company->currency->code),
            'credit' => Money::of(10000, $this->company->currency->code),
        ]);

    JournalEntryLine::factory()
        ->for($journalEntry)
        ->for($this->company)
        ->create([
            'account_id' => $expenseAccount->id,
            'debit' => Money::of(7500, $this->company->currency->code),
            'credit' => Money::zero($this->company->currency->code),
        ]);

    // Get P&L balances
    $balances = $this->fiscalYearService->getProfitAndLossBalances($fiscalYear);

    expect($balances['income']->getAmount()->toFloat())->toBe(10000.00);
    expect($balances['expenses']->getAmount()->toFloat())->toBe(7500.00);
    expect($balances['netIncome']->getAmount()->toFloat())->toBe(2500.00);
});

it('creates closing journal entry with correct amounts', function () {
    // Create fiscal year
    $fiscalYear = FiscalYear::factory()
        ->for($this->company)
        ->forYear(2025)
        ->open()
        ->create();

    // Create accounts
    $incomeAccount = Account::factory()
        ->for($this->company)
        ->create(['type' => AccountType::Income]);

    $expenseAccount = Account::factory()
        ->for($this->company)
        ->create(['type' => AccountType::Expense]);

    $retainedEarningsAccount = Account::factory()
        ->for($this->company)
        ->create(['type' => AccountType::Equity]);

    // Create journal entry with transactions
    $journalEntry = JournalEntry::factory()
        ->for($this->company)
        ->create([
            'entry_date' => \Carbon\Carbon::create(2025, 6, 15),
            'state' => 'posted',
        ]);

    JournalEntryLine::factory()
        ->for($journalEntry)
        ->for($this->company)
        ->create([
            'account_id' => $incomeAccount->id,
            'debit' => Money::zero($this->company->currency->code),
            'credit' => Money::of(10000, $this->company->currency->code),
        ]);

    JournalEntryLine::factory()
        ->for($journalEntry)
        ->for($this->company)
        ->create([
            'account_id' => $expenseAccount->id,
            'debit' => Money::of(7500, $this->company->currency->code),
            'credit' => Money::zero($this->company->currency->code),
        ]);

    // Close the fiscal year
    $dto = new CloseFiscalYearDTO(
        fiscalYear: $fiscalYear,
        retainedEarningsAccountId: $retainedEarningsAccount->id,
        closedByUserId: $this->user->id,
        description: 'Year-end closing',
    );

    $closedFiscalYear = $this->closeFiscalYearAction->execute($dto);

    // Verify closing entry was created
    expect($closedFiscalYear->closingJournalEntry)->not->toBeNull();
    expect($closedFiscalYear->state)->toBe(FiscalYearState::Closed);

    // Verify the closing entry lines
    $closingEntry = $closedFiscalYear->closingJournalEntry;
    expect($closingEntry->lines)->toHaveCount(3);
});

it('transfers net profit to retained earnings', function () {
    $fiscalYear = FiscalYear::factory()
        ->for($this->company)
        ->forYear(2025)
        ->open()
        ->create();

    $incomeAccount = Account::factory()
        ->for($this->company)
        ->create(['type' => AccountType::Income]);

    $retainedEarningsAccount = Account::factory()
        ->for($this->company)
        ->create(['type' => AccountType::Equity]);

    // Create income journal entry
    $journalEntry = JournalEntry::factory()
        ->for($this->company)
        ->create([
            'entry_date' => \Carbon\Carbon::create(2025, 6, 15),
            'state' => 'posted',
        ]);

    $cashAccount = Account::factory()
        ->for($this->company)
        ->create(['type' => AccountType::BankAndCash]);

    JournalEntryLine::factory()
        ->for($journalEntry)
        ->for($this->company)
        ->create([
            'account_id' => $cashAccount->id,
            'debit' => Money::of(10000, $this->company->currency->code),
            'credit' => Money::zero($this->company->currency->code),
        ]);

    JournalEntryLine::factory()
        ->for($journalEntry)
        ->for($this->company)
        ->create([
            'account_id' => $incomeAccount->id,
            'debit' => Money::zero($this->company->currency->code),
            'credit' => Money::of(10000, $this->company->currency->code),
        ]);

    $dto = new CloseFiscalYearDTO(
        fiscalYear: $fiscalYear,
        retainedEarningsAccountId: $retainedEarningsAccount->id,
        closedByUserId: $this->user->id,
    );

    $closedFiscalYear = $this->closeFiscalYearAction->execute($dto);

    // Check retained earnings line is credited (profit)
    $retainedEarningsLine = $closedFiscalYear->closingJournalEntry->lines
        ->where('account_id', $retainedEarningsAccount->id)
        ->first();

    expect($retainedEarningsLine)->not->toBeNull();
    expect($retainedEarningsLine->credit->getAmount()->toFloat())->toBe(10000.00);
});

it('updates fiscal year state after closing', function () {
    $fiscalYear = FiscalYear::factory()
        ->for($this->company)
        ->forYear(2025)
        ->open()
        ->create();

    $retainedEarningsAccount = Account::factory()
        ->for($this->company)
        ->create(['type' => AccountType::Equity]);

    $dto = new CloseFiscalYearDTO(
        fiscalYear: $fiscalYear,
        retainedEarningsAccountId: $retainedEarningsAccount->id,
        closedByUserId: $this->user->id,
    );

    $closedFiscalYear = $this->closeFiscalYearAction->execute($dto);

    expect($closedFiscalYear->state)->toBe(FiscalYearState::Closed);
    expect($closedFiscalYear->closed_at)->not->toBeNull();
    expect($closedFiscalYear->closed_by_user_id)->toBe($this->user->id);
});

it('prevents closing a fiscal year that is not open', function () {
    $fiscalYear = FiscalYear::factory()
        ->for($this->company)
        ->forYear(2025)
        ->closed()
        ->create();

    $retainedEarningsAccount = Account::factory()
        ->for($this->company)
        ->create(['type' => AccountType::Equity]);

    $dto = new CloseFiscalYearDTO(
        fiscalYear: $fiscalYear,
        retainedEarningsAccountId: $retainedEarningsAccount->id,
        closedByUserId: $this->user->id,
    );

    expect(fn () => $this->closeFiscalYearAction->execute($dto))
        ->toThrow(FiscalYearNotReadyToCloseException::class);
});

it('allows reopening if no dependent transactions', function () {
    $fiscalYear = FiscalYear::factory()
        ->for($this->company)
        ->forYear(2025)
        ->open()
        ->create();

    $retainedEarningsAccount = Account::factory()
        ->for($this->company)
        ->create(['type' => AccountType::Equity]);

    // Close the fiscal year
    $dto = new CloseFiscalYearDTO(
        fiscalYear: $fiscalYear,
        retainedEarningsAccountId: $retainedEarningsAccount->id,
        closedByUserId: $this->user->id,
    );

    $this->closeFiscalYearAction->execute($dto);

    // Reopen it
    $reopenedFiscalYear = $this->reopenFiscalYearAction->execute($fiscalYear->fresh(), $this->user->id);

    expect($reopenedFiscalYear->state)->toBe(FiscalYearState::Open);
    expect($reopenedFiscalYear->closing_journal_entry_id)->toBeNull();
    expect($reopenedFiscalYear->closed_at)->toBeNull();
});

it('prevents reopening if new year has transactions', function () {
    $fiscalYear = FiscalYear::factory()
        ->for($this->company)
        ->forYear(2025)
        ->open()
        ->create();

    $retainedEarningsAccount = Account::factory()
        ->for($this->company)
        ->create(['type' => AccountType::Equity]);

    // Close the fiscal year
    $dto = new CloseFiscalYearDTO(
        fiscalYear: $fiscalYear,
        retainedEarningsAccountId: $retainedEarningsAccount->id,
        closedByUserId: $this->user->id,
    );

    $this->closeFiscalYearAction->execute($dto);

    // Create a transaction in the next year
    JournalEntry::factory()
        ->for($this->company)
        ->create([
            'entry_date' => \Carbon\Carbon::create(2026, 1, 15),
            'state' => 'posted',
        ]);

    // Try to reopen
    expect(fn () => $this->reopenFiscalYearAction->execute($fiscalYear->fresh(), $this->user->id))
        ->toThrow(FiscalYearCannotBeReopenedException::class);
});
