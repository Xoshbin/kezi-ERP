<?php

namespace Modules\Accounting\Tests\Feature\Filament;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Accounting\JournalType;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Journals\Pages\CreateJournal;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Journals\Pages\EditJournal;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Journals\Pages\ListJournals;
use Modules\Accounting\Models\Journal;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can render the journal list page', function () {
    livewire(ListJournals::class)
        ->assertSuccessful();
});

it('can list journals', function () {
    $journals = Journal::factory()->count(3)->create([
        'company_id' => $this->company->id,
    ]);

    livewire(ListJournals::class)
        ->assertCanSeeTableRecords($journals);
});

it('can render create journal page', function () {
    livewire(CreateJournal::class)
        ->assertSuccessful();
});

it('can create a new journal', function () {
    $currency = \Modules\Foundation\Models\Currency::factory()->create();

    $newData = [
        'name' => 'Sales Journal',
        'type' => JournalType::Sale->value,
        'short_code' => 'SJ',
        'currency_id' => $currency->id,
        'company_id' => $this->company->id, // Passing it explicitly though it's hidden
    ];

    livewire(CreateJournal::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('journals', [
        'company_id' => $this->company->id,
        'name' => json_encode(['en' => 'Sales Journal']),
        'short_code' => 'SJ',
    ]);
});

it('can render edit journal page', function () {
    $journal = Journal::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(EditJournal::class, [
        'record' => $journal->getRouteKey(),
    ])
        ->assertSuccessful();
});

it('can update a journal', function () {
    $journal = Journal::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(EditJournal::class, [
        'record' => $journal->getRouteKey(),
    ])
        ->fillForm([
            'name' => 'Updated Journal Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($journal->refresh()->getTranslation('name', 'en'))->toBe('Updated Journal Name');
});

it('can delete a journal', function () {
    $journal = Journal::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(EditJournal::class, [
        'record' => $journal->getRouteKey(),
    ])
        ->callAction('delete')
        ->assertHasNoActionErrors();

    $this->assertDatabaseMissing('journals', [
        'id' => $journal->id,
    ]);
});

it('scopes journals to the active company', function () {
    $journalInCompany = Journal::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $otherCompany = \App\Models\Company::factory()->create();
    $journalInOtherCompany = Journal::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    livewire(ListJournals::class)
        ->assertCanSeeTableRecords([$journalInCompany])
        ->assertCanNotSeeTableRecords([$journalInOtherCompany]);
});
