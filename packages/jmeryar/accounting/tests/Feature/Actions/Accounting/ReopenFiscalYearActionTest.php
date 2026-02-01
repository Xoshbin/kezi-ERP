<?php

namespace Jmeryar\Accounting\Tests\Feature\Actions\Accounting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Actions\Accounting\ReopenFiscalYearAction;
use Jmeryar\Accounting\Enums\Accounting\FiscalYearState;
use Jmeryar\Accounting\Enums\Accounting\JournalEntryState;
use Jmeryar\Accounting\Exceptions\FiscalYearCannotBeReopenedException;
use Jmeryar\Accounting\Models\FiscalYear;
use Jmeryar\Accounting\Models\Journal;
use Jmeryar\Accounting\Models\JournalEntry;
use Jmeryar\Accounting\Models\JournalEntryLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->salesJournal = Journal::factory()->for($this->company)->create(['type' => 'sale']);
});

it('can reopen a closed fiscal year', function () {
    // 1. Setup Closed Year with a Closing Entry
    $fiscalYear = FiscalYear::factory()
        ->for($this->company)
        ->closed()
        ->create();

    // Create dummy closing entry
    $closingEntry = JournalEntry::factory()
        ->for($this->company)
        ->create([
            'state' => JournalEntryState::Posted,
            'journal_id' => Journal::factory()->for($this->company)->create(['type' => 'miscellaneous'])->id,
            'source_type' => FiscalYear::class,
            'source_id' => $fiscalYear->id,
            'description' => 'Closing Entry',
        ]);

    // Add dummy lines to be reversed
    JournalEntryLine::factory()->count(2)->state(new \Illuminate\Database\Eloquent\Factories\Sequence(
        ['company_id' => $this->company->id, 'debit' => 100, 'credit' => 0],
        ['company_id' => $this->company->id, 'debit' => 0, 'credit' => 100]
    ))->for($closingEntry)->create();

    $fiscalYear->update(['closing_journal_entry_id' => $closingEntry->id]);

    // 2. Act
    $action = app(ReopenFiscalYearAction::class);
    $reopenedFy = $action->execute($fiscalYear, $this->user->id);

    // 3. Assert
    expect($reopenedFy->state)->toBe(FiscalYearState::Open)
        ->and($reopenedFy->closing_journal_entry_id)->toBeNull()
        ->and($reopenedFy->closed_at)->toBeNull()
        ->and($reopenedFy->closed_by_user_id)->toBeNull();
});

it('cannot reopen if fiscal year is already open', function () {
    $fiscalYear = FiscalYear::factory()
        ->for($this->company)
        ->open()
        ->create();

    $action = app(ReopenFiscalYearAction::class);

    expect(fn () => $action->execute($fiscalYear, $this->user->id))
        ->toThrow(FiscalYearCannotBeReopenedException::class);
});

it('cannot reopen if subsequent transactions exist', function () {
    $year = 2023;
    $fiscalYear = FiscalYear::factory()
        ->for($this->company)
        ->forYear($year)
        ->closed()
        ->create();

    // Create a posted transaction in 2024 (subsequent year)
    $nextYearDate = $fiscalYear->end_date->copy()->addDay();

    JournalEntry::factory()
        ->for($this->company)
        ->create([
            'state' => JournalEntryState::Posted,
            'entry_date' => $nextYearDate,
            'journal_id' => $this->salesJournal->id,
        ]);

    $action = app(ReopenFiscalYearAction::class);

    expect(fn () => $action->execute($fiscalYear, $this->user->id))
        ->toThrow(FiscalYearCannotBeReopenedException::class);
});
