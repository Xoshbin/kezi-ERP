<?php

use Brick\Money\Money;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Tests\Traits\WithConfiguredCompany;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\BankStatement;
use Modules\Accounting\Models\BankStatementLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Accounting\JournalEntryState;
use Modules\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Modules\Accounting\Actions\Accounting\ReverseJournalEntryAction;
use Modules\Accounting\Actions\Accounting\CreateJournalEntryForStatementLineAction;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryForStatementLineDTO;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {

    // Create an original journal entry via the write-off action
    $this->line = BankStatementLine::factory()
        ->for(BankStatement::factory()->for($this->company)->for(Journal::factory()->for($this->company)->create())->for($this->company->currency)->create())
        ->create(['amount' => Money::ofMinor(-100000, $this->company->currency->code), 'is_reconciled' => false]);
    $writeOffAccount = Account::factory()->for($this->company)->create();

    $dto = new CreateJournalEntryForStatementLineDTO(
        bankStatementLine: $this->line,
        writeOffAccount: $writeOffAccount,
        user: $this->user,
        description: 'Original Write Off'
    );
    (app(CreateJournalEntryForStatementLineAction::class))->execute($dto);
    $this->originalJe = JournalEntry::first();
});

it('creates a correct reversing journal entry with inverted amounts', function () {
    // Act
    $reversingJe = (new ReverseJournalEntryAction(app(CreateJournalEntryAction::class)))->execute($this->originalJe, 'Test Reversal', $this->user);

    // Assert
    $this->assertModelExists($reversingJe);
    $this->assertDatabaseHas('journal_entries', [
        'id' => $reversingJe->id,
        'reversed_entry_id' => $this->originalJe->id,
        'reference' => 'REV/' . $this->originalJe->reference,
    ]);

    $originalDebit = $this->originalJe->lines->sum(fn($line) => $line->debit->getMinorAmount()->toInt());
    $originalCredit = $this->originalJe->lines->sum(fn($line) => $line->credit->getMinorAmount()->toInt());

    expect($reversingJe->total_credit->getMinorAmount()->toInt())->toBe($originalDebit);
    expect($reversingJe->total_debit->getMinorAmount()->toInt())->toBe($originalCredit);
});

it('updates the original journal entry state to reversed', function () {
    // Act
    (new ReverseJournalEntryAction(app(CreateJournalEntryAction::class)))->execute($this->originalJe, 'Test Reversal', $this->user);

    // Assert
    $this->assertDatabaseHas('journal_entries', [
        'id' => $this->originalJe->id,
        'state' => JournalEntryState::Reversed->value,
    ]);
});

it('sets the source bank statement line back to unreconciled', function () {
    // Assert initial state
    $this->assertDatabaseHas('bank_statement_lines', [
        'id' => $this->line->id,
        'is_reconciled' => true,
    ]);

    // Act
    (new ReverseJournalEntryAction(app(CreateJournalEntryAction::class)))->execute($this->originalJe, 'Test Reversal', $this->user);

    // Assert final state
    $this->assertDatabaseHas('bank_statement_lines', [
        'id' => $this->line->id,
        'is_reconciled' => false,
    ]);
});

it('is idempotent and does not create multiple reversals for the same entry', function () {
    // Act
    $action = new ReverseJournalEntryAction(app(CreateJournalEntryAction::class));
    $firstReversal = $action->execute($this->originalJe, 'Test Reversal', $this->user);
    $secondReversal = $action->execute($this->originalJe->fresh(), 'Test Reversal', $this->user); // Use fresh model

    // Assert
    $this->assertDatabaseCount('journal_entries', 2); // Original + 1 reversal
    expect($firstReversal->id)->toEqual($secondReversal->id);
});
