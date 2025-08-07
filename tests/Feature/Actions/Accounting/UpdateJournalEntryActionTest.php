<?php

use Brick\Money\Money;
use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalEntry;
use Tests\Traits\CreatesApplication;
use Tests\Traits\WithConfiguredCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Actions\Accounting\UpdateJournalEntryAction;
use App\DataTransferObjects\Accounting\UpdateJournalEntryDTO;
use App\DataTransferObjects\Accounting\UpdateJournalEntryLineDTO;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

test('it updates a journal entry and syncs its lines from a DTO', function () {
    $journalEntry = JournalEntry::factory()->for($this->company)->create([
        'reference' => 'Original Reference',
        'is_posted' => false,
    ]);
    $accountA = Account::factory()->for($this->company)->create();
    $accountB = Account::factory()->for($this->company)->create();

    // Add an initial line that we expect to be removed
    $journalEntry->lines()->create([
        'account_id' => $accountA->id,
        'debit' => Money::of(100, $this->company->currency->code),
        'credit' => Money::of(0, $this->company->currency->code),
    ]);

    // 2. Prepare the DTO with the updated data
    $updateDTO = new UpdateJournalEntryDTO(
        journalEntry: $journalEntry,
        journal_id: $journalEntry->journal_id,
        currency_id: $journalEntry->currency_id,
        entry_date: now()->addDay()->toDateString(), // New date
        reference: 'Updated Reference', // New reference
        description: 'Updated Description',
        is_posted: false,
        lines: [
            new UpdateJournalEntryLineDTO( // A new set of lines
                account_id: $accountB->id,
                debit: '250.00',
                credit: '0.00',
                description: 'New Line 1',
                partner_id: null,
                analytic_account_id: null,
            ),
            new UpdateJournalEntryLineDTO(
                account_id: $accountA->id,
                debit: '0.00',
                credit: '250.00',
                description: 'New Line 2',
                partner_id: null,
                analytic_account_id: null,
            ),
        ]
    );

    // 3. Act
    $action = new UpdateJournalEntryAction();
    $updatedJournalEntry = $action->execute($updateDTO);

    // 4. Assert
    $this->assertDatabaseHas('journal_entries', [
        'id' => $journalEntry->id,
        'reference' => 'Updated Reference',
        'description' => 'Updated Description',
    ]);

    // Assert the old line is gone and the new lines exist
    $this->assertDatabaseCount('journal_entry_lines', 2);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $accountB->id,
        'debit' => 250000,
    ]);

    $expectedTotal = Money::of('250.00', $this->company->currency->code);
    expect($updatedJournalEntry->total_debit->isEqualTo($expectedTotal))->toBeTrue();
});
