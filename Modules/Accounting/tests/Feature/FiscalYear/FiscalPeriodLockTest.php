<?php

namespace Modules\Accounting\Tests\Feature\FiscalYear;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Actions\Accounting\CloseFiscalPeriodAction;
use Modules\Accounting\Actions\Accounting\ReopenFiscalPeriodAction;
use Modules\Accounting\Enums\Accounting\FiscalPeriodState;
use Modules\Accounting\Enums\Accounting\FiscalYearState;
use Modules\Accounting\Enums\Accounting\JournalEntryState;
use Modules\Accounting\Enums\Accounting\LockDateType;
use Modules\Accounting\Exceptions\FiscalPeriodCannotBeReopenedException;
use Modules\Accounting\Exceptions\FiscalPeriodNotReadyToCloseException;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\LockDate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->fiscalYear = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'name' => '2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'state' => FiscalYearState::Open,
    ]);
});

it('closes a fiscal period and updates the lock date', function () {
    $period = FiscalPeriod::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'name' => 'January 2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'state' => FiscalPeriodState::Open,
    ]);

    $action = app(CloseFiscalPeriodAction::class);
    $result = $action->execute($period);

    expect($result->state)->toBe(FiscalPeriodState::Closed);

    // Check lock date was created
    $lockDate = LockDate::where('company_id', $this->company->id)
        ->where('lock_type', LockDateType::AllUsers->value)
        ->first();

    expect($lockDate)->not->toBeNull();
    expect($lockDate->locked_until->format('Y-m-d'))->toBe('2024-01-31');
});

it('prevents closing a period with draft journal entries', function () {
    $period = FiscalPeriod::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'name' => 'January 2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'state' => FiscalPeriodState::Open,
    ]);

    // Create a draft entry in this period
    JournalEntry::factory()->create([
        'company_id' => $this->company->id,
        'entry_date' => '2024-01-15',
        'state' => JournalEntryState::Draft,
    ]);

    $action = app(CloseFiscalPeriodAction::class);

    expect(fn () => $action->execute($period))
        ->toThrow(FiscalPeriodNotReadyToCloseException::class);
});

it('reopens a closed period and adjusts lock date', function () {
    // Create two periods
    $january = FiscalPeriod::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'name' => 'January 2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'state' => FiscalPeriodState::Closed,
    ]);

    $february = FiscalPeriod::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'name' => 'February 2024',
        'start_date' => '2024-02-01',
        'end_date' => '2024-02-29',
        'state' => FiscalPeriodState::Closed,
    ]);

    // Set lock date to February end
    LockDate::create([
        'company_id' => $this->company->id,
        'lock_type' => LockDateType::AllUsers->value,
        'locked_until' => '2024-02-29',
    ]);

    // Reopen February
    $action = app(ReopenFiscalPeriodAction::class);
    $result = $action->execute($february);

    expect($result->state)->toBe(FiscalPeriodState::Open);

    // Lock date should now be January end
    $lockDate = LockDate::where('company_id', $this->company->id)
        ->where('lock_type', LockDateType::AllUsers->value)
        ->first();

    expect($lockDate->locked_until->format('Y-m-d'))->toBe('2024-01-31');
});

it('prevents reopening a period when fiscal year is closed', function () {
    $this->fiscalYear->update(['state' => FiscalYearState::Closed]);

    $period = FiscalPeriod::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'name' => 'January 2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'state' => FiscalPeriodState::Closed,
    ]);

    $action = app(ReopenFiscalPeriodAction::class);

    expect(fn () => $action->execute($period))
        ->toThrow(FiscalPeriodCannotBeReopenedException::class);
});

it('does not regress lock date when closing periods out of order', function () {
    // Close February first
    $february = FiscalPeriod::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'name' => 'February 2024',
        'start_date' => '2024-02-01',
        'end_date' => '2024-02-29',
        'state' => FiscalPeriodState::Open,
    ]);

    app(CloseFiscalPeriodAction::class)->execute($february);

    $lockDate = LockDate::where('company_id', $this->company->id)
        ->where('lock_type', LockDateType::AllUsers->value)
        ->first();

    expect($lockDate->locked_until->format('Y-m-d'))->toBe('2024-02-29');

    // Now close January (earlier period)
    $january = FiscalPeriod::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'name' => 'January 2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
        'state' => FiscalPeriodState::Open,
    ]);

    app(CloseFiscalPeriodAction::class)->execute($january);

    // Lock date should STILL be February (not regressed to January)
    $lockDate->refresh();
    expect($lockDate->locked_until->format('Y-m-d'))->toBe('2024-02-29');
});
