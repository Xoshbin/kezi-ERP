<?php

namespace Modules\Accounting\Tests\Feature\Actions\Accounting;

use Brick\Money\Money;
use Modules\Accounting\Models\Account;
use Tests\Traits\WithConfiguredCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;

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
    $action = app(\Modules\Accounting\Actions\Accounting\CreateJournalEntryAction::class);
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
});
