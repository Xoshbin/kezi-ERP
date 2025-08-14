<?php

namespace Tests\Feature\Actions\Accounting;

use App\Actions\Accounting\CreateJournalEntryAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Enums\Accounting\JournalEntryState;
use App\Models\Account;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

it('creates a journal entry and its lines from a DTO', function () {
    // Arrange: Use the company and user provided by the WithConfiguredCompany trait.
    // We only need to create the specific accounts for this transaction.
    $accountA = Account::factory()->for($this->company)->create();
    $accountB = Account::factory()->for($this->company)->create();
    $currencyCode = $this->company->currency->code;

    $amount = Money::of('150.75', $currencyCode);
    $zero = Money::zero($currencyCode);

    // Prepare the DTO with data from our configured environment.
    $journalEntryDTO = new CreateJournalEntryDTO(
        company_id: $this->company->id,
        journal_id: $this->company->default_sales_journal_id, // Use a default journal.
        currency_id: $this->company->currency_id,
        entry_date: now()->toDateString(),
        reference: 'TEST-001',
        description: 'Test entry from action',
        created_by_user_id: $this->user->id,
        is_posted: false,
        lines: [
            new CreateJournalEntryLineDTO(
                account_id: $accountA->id,
                debit: $amount,
                credit: $zero,
                description: 'Line 1',
                partner_id: null,
                analytic_account_id: null,
            ),
            new CreateJournalEntryLineDTO(
                account_id: $accountB->id,
                debit: $zero,
                credit: $amount,
                description: 'Line 2',
                partner_id: null,
                analytic_account_id: null,
            ),
        ]
    );

    // Act: Execute the action.
    $action = app(CreateJournalEntryAction::class);
    $journalEntry = $action->execute($journalEntryDTO);

    // Assert: The journal entry and its lines were created correctly.
    $this->assertModelExists($journalEntry);
    $this->assertDatabaseHas('journal_entries', [
        'id' => $journalEntry->id,
        'reference' => 'TEST-001',
        'description' => 'Test entry from action',
    ]);

    $this->assertDatabaseCount('journal_entry_lines', 2);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $accountA->id,
        'debit' => 150750, // Stored as minor units (150.75 * 1000 for IQD, assuming 3 decimals)
    ]);

    // Assert: The MoneyCast correctly calculates and hydrates the total amounts.
    $expectedTotal = Money::of('150.75', $currencyCode);
    expect($journalEntry->total_debit->isEqualTo($expectedTotal))->toBeTrue();
    expect($journalEntry->total_credit->isEqualTo($expectedTotal))->toBeTrue();

    // Assert: The journal entry is created with Draft state
    expect($journalEntry->state)->toBe(JournalEntryState::Draft);
    expect($journalEntry->is_posted)->toBeFalse();
});

it('creates journal entries with draft state by default', function () {
    // Arrange
    $accountA = Account::factory()->for($this->company)->create();
    $accountB = Account::factory()->for($this->company)->create();
    $currencyCode = $this->company->currency->code;

    $amount = Money::of('100.00', $currencyCode);
    $zero = Money::zero($currencyCode);

    $journalEntryDTO = new CreateJournalEntryDTO(
        company_id: $this->company->id,
        journal_id: $this->company->default_sales_journal_id,
        currency_id: $this->company->currency_id,
        entry_date: now()->toDateString(),
        reference: 'DRAFT-TEST-001',
        description: 'Test draft entry',
        created_by_user_id: $this->user->id,
        is_posted: false,
        lines: [
            new CreateJournalEntryLineDTO(
                account_id: $accountA->id,
                debit: $amount,
                credit: $zero,
                description: 'Draft line 1',
                partner_id: null,
                analytic_account_id: null,
            ),
            new CreateJournalEntryLineDTO(
                account_id: $accountB->id,
                debit: $zero,
                credit: $amount,
                description: 'Draft line 2',
                partner_id: null,
                analytic_account_id: null,
            ),
        ]
    );

    // Act
    $action = app(CreateJournalEntryAction::class);
    $journalEntry = $action->execute($journalEntryDTO);

    // Assert: The journal entry is created with Draft state, not Posted
    expect($journalEntry->state)->toBe(JournalEntryState::Draft);
    expect($journalEntry->is_posted)->toBeFalse();

    // Assert: Database record has correct state
    $this->assertDatabaseHas('journal_entries', [
        'id' => $journalEntry->id,
        'state' => JournalEntryState::Draft->value,
        'is_posted' => false,
    ]);
});
