<?php

use App\Actions\Accounting\CreateJournalEntryAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

test('it creates a journal entry and its lines from a DTO', function () {
    // 1. Arrange
    $company = $this->createConfiguredCompany();
    $user = User::factory()->for($company)->create();
    $journal = Journal::factory()->for($company)->create();
    $accountA = Account::factory()->for($company)->create();
    $accountB = Account::factory()->for($company)->create();
    $currencyCode = $company->currency->code;

    $amount = Money::of('150.75', $currencyCode);
    $zero = Money::zero($currencyCode);

    // 2. Prepare the DTO
    $journalEntryDTO = new CreateJournalEntryDTO(
        company_id: $company->id,
        journal_id: $journal->id,
        currency_id: $company->currency_id,
        entry_date: now()->toDateString(),
        reference: 'TEST-001',
        description: 'Test entry from action',
        created_by_user_id: $user->id,
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

    // 3. Act
    $action = app(CreateJournalEntryAction::class);
    $journalEntry = $action->execute($journalEntryDTO);

    // 4. Assert
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
        'debit' => 150750, // Stored as minor units
    ]);

    $expectedTotal = Money::of('150.75', $currencyCode);
    expect($journalEntry->total_debit->isEqualTo($expectedTotal))->toBeTrue();
    expect($journalEntry->total_credit->isEqualTo($expectedTotal))->toBeTrue();
});
