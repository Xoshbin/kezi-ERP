<?php

namespace Kezi\Accounting\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\Pages\CreateJournalEntry;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\Pages\ListJournalEntries;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Accounting\Models\JournalEntry;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
});

it('can render the journal entry list page', function () {
    livewire(ListJournalEntries::class)
        ->assertSuccessful();
});

it('can list journal entries', function () {
    $entries = JournalEntry::factory()->count(3)->create([
        'company_id' => $this->company->id,
    ]);

    livewire(ListJournalEntries::class)
        ->assertCanSeeTableRecords($entries)
        ->assertCountTableRecords(3);
});

it('can render create journal entry page', function () {
    livewire(CreateJournalEntry::class)
        ->assertSuccessful();
});

it('can create a new journal entry', function () {
    $journal = Journal::factory()->create(['company_id' => $this->company->id]);
    $account1 = Account::factory()->create(['company_id' => $this->company->id]);
    $account2 = Account::factory()->create(['company_id' => $this->company->id]);

    $newData = [
        'journal_id' => $journal->id,
        'entry_date' => now()->format('Y-m-d'),
        'currency_id' => $this->company->currency_id,
        'exchange_rate' => 1,
        'lines' => [
            ['account_id' => $account1->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $account2->id, 'debit' => 0, 'credit' => 100],
        ],
    ];

    livewire(CreateJournalEntry::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('journal_entries', [
        'company_id' => $this->company->id,
        'journal_id' => $journal->id,
    ]);
});

it('scopes journal entries to the active company', function () {
    $entryInCompany = JournalEntry::factory()->create([
        'company_id' => $this->company->id,
        'entry_number' => 'JE-IN-COMPANY',
    ]);

    $otherCompany = \App\Models\Company::factory()->create();
    $entryInOtherCompany = JournalEntry::factory()->create([
        'company_id' => $otherCompany->id,
        'entry_number' => 'JE-OUT-COMPANY',
    ]);

    livewire(ListJournalEntries::class)
        ->searchTable('JE')
        ->assertCanSeeTableRecords([$entryInCompany])
        ->assertCanNotSeeTableRecords([$entryInOtherCompany]);
});
