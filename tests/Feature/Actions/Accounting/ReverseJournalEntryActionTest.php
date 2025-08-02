<?php

use App\Actions\Accounting\CreateJournalEntryForStatementLineAction;
use App\Actions\Accounting\ReverseJournalEntryAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryForStatementLineDTO;
use App\Enums\Accounting\JournalEntryState;
use App\Models\Account;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

beforeEach(function () {
    $this->createConfiguredCompany();
    $this->company = Company::first();
    $this->user = User::factory()->for($this->company)->create();
    $this->actingAs($this->user);

    // Use the company's default currency for consistency
    $currency = $this->company->currency;
    $currencyCode = $currency->code;

    // Create an original journal entry via the write-off action
    $this->line = BankStatementLine::factory()
        ->for(BankStatement::factory()->for($this->company)->for(Journal::factory()->for($this->company)->create())->for($currency)->create())
        ->create(['amount' => \Brick\Money\Money::ofMinor(-100000, $currencyCode), 'is_reconciled' => false]);
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
    $reversingJe = (new ReverseJournalEntryAction(app(\App\Actions\Accounting\CreateJournalEntryAction::class)))->execute($this->originalJe);

    // Assert
    $this->assertModelExists($reversingJe);
    $this->assertDatabaseHas('journal_entries', [
        'id' => $reversingJe->id,
        'reversed_entry_id' => $this->originalJe->id,
        'reference' => 'REV: ' . $this->originalJe->reference,
    ]);

    $originalDebit = $this->originalJe->lines->sum(fn($line) => $line->debit->getMinorAmount()->toInt());
    $originalCredit = $this->originalJe->lines->sum(fn($line) => $line->credit->getMinorAmount()->toInt());

    expect($reversingJe->total_credit->getMinorAmount()->toInt())->toBe($originalDebit);
    expect($reversingJe->total_debit->getMinorAmount()->toInt())->toBe($originalCredit);
});

it('updates the original journal entry state to reversed', function () {
    // Act
    (new ReverseJournalEntryAction(app(\App\Actions\Accounting\CreateJournalEntryAction::class)))->execute($this->originalJe);

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
    (new ReverseJournalEntryAction(app(\App\Actions\Accounting\CreateJournalEntryAction::class)))->execute($this->originalJe);

    // Assert final state
    $this->assertDatabaseHas('bank_statement_lines', [
        'id' => $this->line->id,
        'is_reconciled' => false,
    ]);
});

it('is idempotent and does not create multiple reversals for the same entry', function () {
    // Act
    $action = new ReverseJournalEntryAction(app(\App\Actions\Accounting\CreateJournalEntryAction::class));
    $firstReversal = $action->execute($this->originalJe);
    $secondReversal = $action->execute($this->originalJe->fresh()); // Use fresh model

    // Assert
    $this->assertDatabaseCount('journal_entries', 2); // Original + 1 reversal
    expect($firstReversal->id)->toEqual($secondReversal->id);
});
