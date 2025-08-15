<?php

use App\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\User;
use Brick\Money\Money;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can render the list page', function () {
    $this->get(JournalEntryResource::getUrl('index'))->assertSuccessful();
});

it('can render the create page', function () {
    $this->get(JournalEntryResource::getUrl('create'))->assertSuccessful();
});

it('can create a journal entry', function () {
    livewire(\App\Filament\Resources\JournalEntries\Pages\CreateJournalEntry::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'journal_id' => $this->company->default_bank_journal_id,
            'currency_id' => $this->company->currency_id,
            'entry_date' => now()->format('Y-m-d'),
            'reference' => 'Test Reference',
            'description' => 'Test Description',
            'lines' => [
                [
                    'account_id' => $this->company->default_bank_account_id,
                    'debit' => 100,
                    'credit' => 0,
                    'partner_id' => null,
                    'analytic_account_id' => null,
                    'description' => 'Line 1',
                ],
                [
                    'account_id' => $this->company->default_accounts_payable_id,
                    'debit' => 0,
                    'credit' => 100,
                    'partner_id' => null,
                    'analytic_account_id' => null,
                    'description' => 'Line 2',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('journal_entries', [
        'reference' => 'Test Reference',
    ]);

    $journalEntry = JournalEntry::where('reference', 'Test Reference')->firstOrFail();
    $this->assertCount(2, $journalEntry->lines);
    $this->assertTrue($journalEntry->total_debit->isEqualTo(Money::of(100, $this->company->currency->code)));
    $this->assertTrue($journalEntry->total_credit->isEqualTo(Money::of(100, $this->company->currency->code)));

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'description' => 'Line 1',
        'debit' => 100000,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'description' => 'Line 2',
        'credit' => 100000,
    ]);
});

it('can validate input', function () {
    livewire(\App\Filament\Resources\JournalEntries\Pages\CreateJournalEntry::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'journal_id' => $this->company->default_bank_journal_id,
            'currency_id' => $this->company->currency_id,
            'entry_date' => now()->format('Y-m-d'),
            'reference' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['reference' => 'required']);
});

it('can render the edit page', function () {
    $journalEntry = JournalEntry::factory()->for($this->company)->create();
    $this->get(JournalEntryResource::getUrl('edit', ['record' => $journalEntry]))->assertSuccessful();
});

it('can edit a journal entry', function () {
    $journalEntry = JournalEntry::factory()->for($this->company)->create();

    livewire(\App\Filament\Resources\JournalEntries\Pages\EditJournalEntry::class, [
        'record' => $journalEntry->getRouteKey(),
    ])
        ->fillForm([
            'reference' => 'Updated Reference',
            'lines' => [
                [
                    'account_id' => $this->company->default_bank_account_id,
                    'debit' => 200,
                    'credit' => 0,
                    'partner_id' => null,
                    'analytic_account_id' => null,
                    'description' => 'Line 1 updated',
                ],
                [
                    'account_id' => $this->company->default_accounts_payable_id,
                    'debit' => 0,
                    'credit' => 200,
                    'partner_id' => null,
                    'analytic_account_id' => null,
                    'description' => 'Line 2 updated',
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('journal_entries', [
        'id' => $journalEntry->id,
        'reference' => 'Updated Reference',
    ]);
});

it('can delete a journal entry', function () {
    $journalEntry = JournalEntry::factory()->for($this->company)->create(['is_posted' => false]);

    livewire(\App\Filament\Resources\JournalEntries\Pages\EditJournalEntry::class, [
        'record' => $journalEntry->getRouteKey(),
    ])
        ->callAction(DeleteAction::class);

    $this->assertModelMissing($journalEntry);
});

it('can display correct major amount in edit form', function () {
    // Arrange
    $currency = Currency::where('code', 'IQD')->firstOrFail();

    $journalEntry = JournalEntry::factory()
        ->for($this->company)
        ->for($currency)
        ->create();

    $line = $journalEntry->lines()->create([
        'account_id' => $this->company->default_bank_account_id,
        'debit' => Money::of(15000, 'IQD'), // 15,000 major units
        'credit' => Money::of(0, 'IQD'),
        'description' => 'Test line for edit form',
    ]);

    // Sanity check: ensure it's stored as minor units in the database
    $this->assertDatabaseHas('journal_entry_lines', [
        'id' => $line->id,
        'debit' => 15000000,
    ]);

    // Act & Assert
    $livewire = livewire(\App\Filament\Resources\JournalEntries\Pages\EditJournalEntry::class, [
        'record' => $journalEntry->getRouteKey(),
    ]);

    $lines = $livewire->get('data.lines');
    $firstLineKey = array_key_first($lines);

    $livewire->assertFormSet([
        "lines.{$firstLineKey}.debit" => '15000.000',
        "lines.{$firstLineKey}.credit" => '0.000',
    ]);
});


it('reactively updates totals when lines change', function () {
    $wire = livewire(\App\Filament\Resources\JournalEntries\Pages\CreateJournalEntry::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'journal_id' => $this->company->default_bank_journal_id,
            'currency_id' => $this->company->currency_id,
            'entry_date' => now()->format('Y-m-d'),
            'reference' => 'Reactive Test',
        ]);

    // Initial state should be zero, but since we use ->numeric() it will be null
    $wire->assertFormSet([
        'total_debit' => null,
        'total_credit' => null,
        'balance' => null,
    ]);

    // Add first line
    $wire->set('data.lines', [
        [
            'account_id' => $this->company->default_bank_account_id,
            'debit' => 100,
            'credit' => 0,
            'description' => 'Line 1',
        ]
    ])
    ->assertFormSet([
        'total_debit' => 100.0,
        'total_credit' => 0.0,
        'balance' => 100.0,
    ]);

    // Add second line
    $wire->set('data.lines', [
        [
            'account_id' => $this->company->default_bank_account_id,
            'debit' => 100,
            'credit' => 0,
            'description' => 'Line 1',
        ],
        [
            'account_id' => $this->company->default_accounts_payable_id,
            'debit' => 0,
            'credit' => 50,
            'description' => 'Line 2',
        ]
    ])
    ->assertFormSet([
        'total_debit' => 100.0,
        'total_credit' => 50.0,
        'balance' => 50.0,
    ]);

    // Update a line
    $lines = $wire->get('data.lines');
    $firstLineKey = array_key_first($lines);
    $wire->set("data.lines.{$firstLineKey}.debit", 250)
        ->assertFormSet([
            'total_debit' => 250.0,
            'total_credit' => 50.0,
            'balance' => 200.0,
        ]);

    // Remove a line
    $lines = $wire->get('data.lines');
    array_pop($lines);
    $wire->set('data.lines', $lines)
        ->assertFormSet([
            'total_debit' => 250.0,
            'total_credit' => 0.0,
            'balance' => 250.0,
        ]);
});

it('calculates and fills totals on edit page load', function () {
    // Arrange
    $journalEntry = JournalEntry::factory()->for($this->company)->create();
    $journalEntry->lines()->create([
        'account_id' => $this->company->default_bank_account_id,
        'debit' => Money::of(500, $this->company->currency->code),
        'credit' => Money::of(0, $this->company->currency->code),
    ]);
    $journalEntry->lines()->create([
        'account_id' => $this->company->default_accounts_payable_id,
        'debit' => Money::of(0, $this->company->currency->code),
        'credit' => Money::of(200, $this->company->currency->code),
    ]);

    // Act & Assert
    livewire(\App\Filament\Resources\JournalEntries\Pages\EditJournalEntry::class, [
        'record' => $journalEntry->getRouteKey(),
    ])
    ->assertFormSet([
        'total_debit' => '500.000',
        'total_credit' => '200.000',
        'balance' => '300.000',
    ]);
});
