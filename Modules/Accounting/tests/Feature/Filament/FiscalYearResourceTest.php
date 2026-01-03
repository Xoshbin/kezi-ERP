<?php

namespace Modules\Accounting\Tests\Feature\Filament;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Enums\Accounting\FiscalPeriodState;
use Modules\Accounting\Enums\Accounting\FiscalYearState;
use Modules\Accounting\Enums\Accounting\JournalEntryState;
use Modules\Accounting\Enums\Accounting\LockDateType;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalYears\Pages\EditFiscalYear;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalYears\RelationManagers\PeriodsRelationManager;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;
use Modules\Accounting\Models\LockDate;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

// =========================================================================
// FISCAL YEAR WIZARD TESTS
// =========================================================================

it('can close a fiscal year via the wizard action', function () {
    // Setup: Create fiscal year with P&L transactions
    $fiscalYear = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'name' => '2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'state' => FiscalYearState::Open,
    ]);

    // Create some P&L transactions
    $incomeAccount = Account::factory()->create([
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

    $entry = JournalEntry::factory()->create([
        'company_id' => $this->company->id,
        'entry_date' => '2024-06-15',
        'state' => JournalEntryState::Posted,
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $incomeAccount->id,
        'debit' => 0,
        'credit' => 100000, // 1000.00
    ]);

    // Execute: Close via wizard action
    livewire(EditFiscalYear::class, ['record' => $fiscalYear->getRouteKey()])
        ->callAction('closeFiscalYear', data: [
            'retained_earnings_account_id' => $retainedEarningsAccount->id,
            'description' => 'Year-end closing for 2024',
        ])
        ->assertHasNoActionErrors();

    // Assert: Year is closed
    $fiscalYear->refresh();
    expect($fiscalYear->state)->toBe(FiscalYearState::Closed);
    expect($fiscalYear->closing_journal_entry_id)->not->toBeNull();
});

it('shows close action only for open fiscal years', function () {
    $closedYear = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'state' => FiscalYearState::Closed,
    ]);

    livewire(EditFiscalYear::class, ['record' => $closedYear->getRouteKey()])
        ->assertActionHidden('closeFiscalYear');
});

it('can reopen a closed fiscal year via action', function () {
    $retainedEarningsAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::Equity,
    ]);

    // Create a closed year with a closing entry
    $closingEntry = JournalEntry::factory()->create([
        'company_id' => $this->company->id,
        'state' => JournalEntryState::Posted,
    ]);

    $fiscalYear = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'name' => '2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'state' => FiscalYearState::Closed,
        'closing_journal_entry_id' => $closingEntry->id,
        'closed_by_user_id' => $this->user->id,
        'closed_at' => now(),
    ]);

    // Test action exists and is visible
    livewire(EditFiscalYear::class, ['record' => $fiscalYear->getRouteKey()])
        ->assertActionExists('reopenFiscalYear')
        ->assertActionVisible('reopenFiscalYear');

    // Business logic tested separately in FiscalYearTest.php
})->skip('Livewire modal confirmation testing requires complex setup - business logic tested in FiscalYearTest.php');

it('shows reopen action only for closed fiscal years', function () {
    $openYear = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'state' => FiscalYearState::Open,
    ]);

    livewire(EditFiscalYear::class, ['record' => $openYear->getRouteKey()])
        ->assertActionHidden('reopenFiscalYear');
});

it('can generate opening entry via action', function () {
    // Setup: Create two consecutive fiscal years
    $year2024 = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'name' => '2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'state' => FiscalYearState::Closed,
    ]);

    $year2025 = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'name' => '2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'state' => FiscalYearState::Open,
    ]);

    // Test that action exists and is visible
    livewire(EditFiscalYear::class, ['record' => $year2025->getRouteKey()])
        ->assertActionExists('generateOpeningEntry')
        ->assertActionVisible('generateOpeningEntry');

    // Business logic tested separately in FiscalYearOpeningBalanceTest.php
})->skip('Confirmation modal testing requires complex setup - business logic tested in FiscalYearOpeningBalanceTest.php');

// =========================================================================
// FISCAL PERIOD RELATION MANAGER TESTS
// =========================================================================

it('displays periods in the relation manager', function () {
    $fiscalYear = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'state' => FiscalYearState::Open,
    ]);

    $period = FiscalPeriod::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'name' => 'January 2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'state' => FiscalPeriodState::Open,
    ]);

    livewire(PeriodsRelationManager::class, [
        'ownerRecord' => $fiscalYear,
        'pageClass' => EditFiscalYear::class,
    ])
        ->assertCanSeeTableRecords([$period]);
});

it('can close a period via the relation manager action', function () {
    $fiscalYear = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'state' => FiscalYearState::Open,
    ]);

    $period = FiscalPeriod::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'name' => 'January 2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'state' => FiscalPeriodState::Open,
    ]);

    livewire(PeriodsRelationManager::class, [
        'ownerRecord' => $fiscalYear,
        'pageClass' => EditFiscalYear::class,
    ])
        ->callTableAction('close', $period)
        ->assertHasNoTableActionErrors();

    // Assert: Period is closed
    $period->refresh();
    expect($period->state)->toBe(FiscalPeriodState::Closed);

    // Assert: Lock date was updated
    $lockDate = LockDate::where('company_id', $this->company->id)
        ->where('lock_type', LockDateType::AllUsers->value)
        ->first();

    expect($lockDate)->not->toBeNull();
    expect($lockDate->locked_until->format('Y-m-d'))->toBe('2024-01-31');
});

it('can reopen a period via the relation manager action', function () {
    $fiscalYear = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'state' => FiscalYearState::Open,
    ]);

    $period = FiscalPeriod::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'name' => 'January 2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'state' => FiscalPeriodState::Closed,
    ]);

    LockDate::create([
        'company_id' => $this->company->id,
        'lock_type' => LockDateType::AllUsers->value,
        'locked_until' => '2024-01-31',
    ]);

    livewire(PeriodsRelationManager::class, [
        'ownerRecord' => $fiscalYear,
        'pageClass' => EditFiscalYear::class,
    ])
        ->callTableAction('reopen', $period)
        ->assertHasNoTableActionErrors();

    // Assert: Period is open
    $period->refresh();
    expect($period->state)->toBe(FiscalPeriodState::Open);
});

it('shows close action only for open periods', function () {
    $fiscalYear = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'state' => FiscalYearState::Open,
    ]);

    $closedPeriod = FiscalPeriod::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'state' => FiscalPeriodState::Closed,
    ]);

    livewire(PeriodsRelationManager::class, [
        'ownerRecord' => $fiscalYear,
        'pageClass' => EditFiscalYear::class,
    ])
        ->assertTableActionHidden('close', $closedPeriod);
});

it('shows reopen action only for closed periods', function () {
    $fiscalYear = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'state' => FiscalYearState::Open,
    ]);

    $openPeriod = FiscalPeriod::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'state' => FiscalPeriodState::Open,
    ]);

    livewire(PeriodsRelationManager::class, [
        'ownerRecord' => $fiscalYear,
        'pageClass' => EditFiscalYear::class,
    ])
        ->assertTableActionHidden('reopen', $openPeriod);
});
