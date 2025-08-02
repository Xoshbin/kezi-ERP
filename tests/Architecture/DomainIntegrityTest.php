<?php

use App\Actions\Accounting\CreateJournalEntryAction;
use App\Actions\Accounting\ReverseJournalEntryAction;
use App\Actions\Accounting\UpdateJournalEntryAction;
use App\Models\JournalEntry;

test('models do not use the DB facade directly')
    ->expect('App\Models')
    ->not->toUse('Illuminate\Support\Facades\DB');

test('only actions can change the state of a journal entry')
    ->expect(JournalEntry::class)
    ->toOnlyBeChangedBy([
        CreateJournalEntryAction::class,
        UpdateJournalEntryAction::class,
        ReverseJournalEntryAction::class,
    ]);
