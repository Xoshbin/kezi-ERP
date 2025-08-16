<?php

use App\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Journal;
use App\Models\JournalEntry;
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
        'company_id' => $this->company->id,
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


it('can create capital injection journal entry following Step 4 scenario', function () {
    // Arrange: Create the specific accounts needed for the capital injection scenario
    $bankAccount = Account::factory()->for($this->company)->create([
        'code' => '1010',
        'name' => 'Bank',
        'type' => 'bank_and_cash'
    ]);

    $ownersEquityAccount = Account::factory()->for($this->company)->create([
        'code' => '3000',
        'name' => "Owner's Equity",
        'type' => 'equity'
    ]);

    // Create a Bank journal for the transaction
    $bankJournal = Journal::factory()->for($this->company)->create([
        'name' => 'Bank',
        'type' => 'bank'
    ]);

    // Act: Create the capital injection journal entry
    $wire = livewire(\App\Filament\Resources\JournalEntries\Pages\CreateJournalEntry::class)
        ->fillForm([
            'journal_id' => $bankJournal->id,
            'currency_id' => $this->company->currency_id,
            'entry_date' => now()->format('Y-m-d'),
            'reference' => 'Initial Capital Investment',
            'description' => "Soran's personal funds transferred to the Jmeryar ERP bank account",
            'lines' => [
                [
                    'account_id' => $bankAccount->id,
                    'debit' => 15000000,
                    'credit' => 0,
                    'partner_id' => null,
                    'analytic_account_id' => null,
                    'description' => 'Capital injection into company bank account',
                ],
                [
                    'account_id' => $ownersEquityAccount->id,
                    'debit' => 0,
                    'credit' => 15000000,
                    'partner_id' => null,
                    'analytic_account_id' => null,
                    'description' => "Owner's personal investment",
                ],
            ],
        ]);

    // This should expose the issue with the create button when there are lines
    $wire->call('create')
        ->assertHasNoFormErrors();

    // Assert: Verify the journal entry was created in draft state
    $this->assertDatabaseHas('journal_entries', [
        'reference' => 'Initial Capital Investment',
        'description' => "Soran's personal funds transferred to the Jmeryar ERP bank account",
        'is_posted' => false, // Initially in draft state
    ]);

    $journalEntry = JournalEntry::where('reference', 'Initial Capital Investment')->firstOrFail();

    // Verify the journal entry structure
    expect($journalEntry->journal_id)->toBe($bankJournal->id);
    expect($journalEntry->currency_id)->toBe($this->company->currency_id);
    expect($journalEntry->created_by_user_id)->toBe($this->user->id);

    // Verify the lines were created correctly
    $this->assertCount(2, $journalEntry->lines);

    // Verify Bank account debit line
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $bankAccount->id,
        'debit' => 15000000000, // Stored in minor units (IQD fils)
        'credit' => 0,
        'description' => 'Capital injection into company bank account',
    ]);

    // Verify Owner's Equity credit line
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $ownersEquityAccount->id,
        'debit' => 0,
        'credit' => 15000000000, // Stored in minor units (IQD fils)
        'description' => "Owner's personal investment",
    ]);

    // Verify totals are calculated correctly
    $this->assertTrue($journalEntry->total_debit->isEqualTo(Money::of(15000000, $this->company->currency->code)));
    $this->assertTrue($journalEntry->total_credit->isEqualTo(Money::of(15000000, $this->company->currency->code)));

    // Verify the entry is balanced
    $this->assertTrue($journalEntry->total_debit->isEqualTo($journalEntry->total_credit));
});

it('can create and post capital injection journal entry using Filament interface', function () {
    // Debug: Check currency setup
    expect($this->company->currency_id)->not->toBeNull();
    expect($this->company->currency->code)->toBe('IQD');

    // Set up currency rates for the test (IQD to IQD should be 1.0)
    \App\Models\CurrencyRate::create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id, // IQD
        'rate' => 1.0,
        'effective_date' => now()->format('Y-m-d'),
        'source' => 'manual',
    ]);

    // Arrange: Create the specific accounts needed for the capital injection scenario
    $bankAccount = Account::factory()->for($this->company)->create([
        'code' => '1010',
        'name' => 'Bank',
        'type' => 'bank_and_cash'
    ]);

    $ownersEquityAccount = Account::factory()->for($this->company)->create([
        'code' => '3000',
        'name' => "Owner's Equity",
        'type' => 'equity'
    ]);

    // Create a Bank journal for the transaction
    $bankJournal = Journal::factory()->for($this->company)->create([
        'name' => 'Bank',
        'type' => 'bank'
    ]);

    // Use a unique reference to avoid duplicate entry issues
    $uniqueReference = 'Capital Investment Test ' . now()->timestamp;

    // Act: Create the capital injection journal entry using Filament form
    livewire(\App\Filament\Resources\JournalEntries\Pages\CreateJournalEntry::class)
        ->fillForm([
            'journal_id' => $bankJournal->id,
            'currency_id' => $this->company->currency_id,
            'entry_date' => now()->format('Y-m-d'),
            'reference' => $uniqueReference,
            'description' => "Soran's personal funds transferred to the Jmeryar ERP bank account",
            'lines' => [
                [
                    'account_id' => $bankAccount->id,
                    'debit' => 15000000,
                    'credit' => 0,
                    'partner_id' => null,
                    'analytic_account_id' => null,
                    'description' => 'Capital injection into company bank account',
                ],
                [
                    'account_id' => $ownersEquityAccount->id,
                    'debit' => 0,
                    'credit' => 15000000,
                    'partner_id' => null,
                    'analytic_account_id' => null,
                    'description' => "Owner's personal investment",
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Assert: Verify the journal entry was created in draft state
    $this->assertDatabaseHas('journal_entries', [
        'reference' => $uniqueReference,
        'description' => "Soran's personal funds transferred to the Jmeryar ERP bank account",
        'is_posted' => false, // Initially in draft state
    ]);

    $journalEntry = JournalEntry::where('reference', $uniqueReference)->firstOrFail();

    // Verify the entry is in draft state
    expect($journalEntry->is_posted)->toBeFalse();
    expect($journalEntry->hash)->toBeNull(); // Hash should be null for draft entries

    // Act: Now post the journal entry using the Filament post action
    $editWire = livewire(\App\Filament\Resources\JournalEntries\Pages\EditJournalEntry::class, [
        'record' => $journalEntry->getRouteKey(),
    ]);

    // Verify the post action is visible for draft entries
    $editWire->assertActionVisible('post');

    // Call the post action - this should post the journal entry
    $editWire->callAction('post');

    // Assert: Verify posting was successful
    $journalEntry->refresh();

    // Verify the entry is now posted
    expect($journalEntry->is_posted)->toBeTrue();

    // Verify hash is generated for audit trail
    expect($journalEntry->hash)->not->toBeNull();

    // Verify totals are correctly calculated
    $this->assertTrue($journalEntry->total_debit->isEqualTo(Money::of(15000000, $this->company->currency->code)));
    $this->assertTrue($journalEntry->total_credit->isEqualTo(Money::of(15000000, $this->company->currency->code)));

    // Verify the entry is balanced
    $this->assertTrue($journalEntry->total_debit->isEqualTo($journalEntry->total_credit));

    // Verify the post action is no longer visible for posted entries
    $editWire = livewire(\App\Filament\Resources\JournalEntries\Pages\EditJournalEntry::class, [
        'record' => $journalEntry->getRouteKey(),
    ]);
    $editWire->assertActionHidden('post');

    // Verify immutability: Attempt to update posted entry should throw exception
    $journalEntry->description = 'Attempted unauthorized update';

    expect(fn() => $journalEntry->save())
        ->toThrow(\RuntimeException::class, "Attempted to modify immutable posted journal entry field: 'description'");

    // Verify the description was not changed in the database
    $this->assertDatabaseHas('journal_entries', [
        'id' => $journalEntry->id,
        'description' => "Soran's personal funds transferred to the Jmeryar ERP bank account",
    ]);

    // Verify deletion is prevented for posted entries
    $journalEntryService = app(\App\Services\JournalEntryService::class);
    expect(fn() => $journalEntryService->delete($journalEntry))
        ->toThrow(\App\Exceptions\DeletionNotAllowedException::class, 'Cannot delete a posted journal entry');

    // Verify the accounting equation: Assets = Liabilities + Equity
    // Bank (Asset) increased by 15,000,000 IQD
    // Owner's Equity increased by 15,000,000 IQD
    // The equation remains balanced: Assets (+15M) = Equity (+15M)

    // Verify created_by_user_id and created_at are immutable
    expect($journalEntry->created_by_user_id)->toBe($this->user->id);
    expect($journalEntry->created_at)->not->toBeNull();
});

it('shows proper error when trying to create duplicate reference', function () {
    // Arrange: Create the specific accounts needed for the capital injection scenario
    $bankAccount = Account::factory()->for($this->company)->create([
        'code' => '1010',
        'name' => 'Bank',
        'type' => 'bank_and_cash'
    ]);

    $ownersEquityAccount = Account::factory()->for($this->company)->create([
        'code' => '3000',
        'name' => "Owner's Equity",
        'type' => 'equity'
    ]);

    // Create a Bank journal for the transaction
    $bankJournal = Journal::factory()->for($this->company)->create([
        'name' => 'Bank',
        'type' => 'bank'
    ]);

    // First, create a journal entry successfully
    $wire = livewire(\App\Filament\Resources\JournalEntries\Pages\CreateJournalEntry::class)
        ->fillForm([
            'journal_id' => $bankJournal->id,
            'currency_id' => $this->company->currency_id,
            'entry_date' => now()->format('Y-m-d'),
            'reference' => 'Duplicate Reference Test',
            'description' => "First journal entry",
            'lines' => [
                [
                    'account_id' => $bankAccount->id,
                    'debit' => 1000,
                    'credit' => 0,
                    'partner_id' => null,
                    'analytic_account_id' => null,
                    'description' => 'First entry debit',
                ],
                [
                    'account_id' => $ownersEquityAccount->id,
                    'debit' => 0,
                    'credit' => 1000,
                    'partner_id' => null,
                    'analytic_account_id' => null,
                    'description' => 'First entry credit',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify the first entry was created
    $this->assertDatabaseHas('journal_entries', [
        'reference' => 'Duplicate Reference Test',
        'description' => 'First journal entry',
    ]);

    // Debug: Check how many entries exist with this reference
    $count = JournalEntry::where('reference', 'Duplicate Reference Test')->count();
    expect($count)->toBe(1, 'First entry should be created');

    // Act: Try to create another journal entry with the same reference
    $wire = livewire(\App\Filament\Resources\JournalEntries\Pages\CreateJournalEntry::class)
        ->fillForm([
            'journal_id' => $bankJournal->id,
            'currency_id' => $this->company->currency_id,
            'entry_date' => now()->format('Y-m-d'),
            'reference' => 'Duplicate Reference Test', // Same reference as before
            'description' => "Second journal entry with duplicate reference",
            'lines' => [
                [
                    'account_id' => $bankAccount->id,
                    'debit' => 2000,
                    'credit' => 0,
                    'partner_id' => null,
                    'analytic_account_id' => null,
                    'description' => 'Second entry debit',
                ],
                [
                    'account_id' => $ownersEquityAccount->id,
                    'debit' => 0,
                    'credit' => 2000,
                    'partner_id' => null,
                    'analytic_account_id' => null,
                    'description' => 'Second entry credit',
                ],
            ],
        ]);

    // Assert: Our error handling should prevent the duplicate entry from being created
    $wire->call('create');

    // The duplicate should be prevented - check that only one entry exists
    $count = JournalEntry::where('reference', 'Duplicate Reference Test')->count();
    expect($count)->toBe(1, 'Duplicate entry should be prevented by our error handling');

    // Verify the second entry was NOT created
    $this->assertDatabaseMissing('journal_entries', [
        'reference' => 'Duplicate Reference Test',
        'description' => 'Second journal entry with duplicate reference',
    ]);

    // Verify only one entry exists with this reference
    $count = JournalEntry::where('reference', 'Duplicate Reference Test')->count();
    expect($count)->toBe(1);
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
        'company_id' => $this->company->id,
        'account_id' => $this->company->default_bank_account_id,
        'debit' => Money::of(500, $this->company->currency->code),
        'credit' => Money::of(0, $this->company->currency->code),
    ]);
    $journalEntry->lines()->create([
        'company_id' => $this->company->id,
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
