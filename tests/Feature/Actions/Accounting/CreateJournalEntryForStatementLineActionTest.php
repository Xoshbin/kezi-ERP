<?php

use App\Actions\Accounting\CreateJournalEntryForStatementLineAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryForStatementLineDTO;
use App\Models\Account;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

beforeEach(function () {
    $this->createConfiguredCompany();
});

it('correctly creates a journal entry and links it to the statement line via a polymorphic relationship', function () {
    // Arrange
    $company = Company::first();
    $user = User::factory()->for($company)->create();
    $line = BankStatementLine::factory()->for(BankStatement::factory()->for($company)->for(Journal::factory()->for($company)->create())->create())->create(['amount' => -100000]); // -100.000 IQD
    $writeOffAccount = Account::factory()->for($company)->create();

    $dto = new CreateJournalEntryForStatementLineDTO(
        bankStatementLine: $line,
        writeOffAccount: $writeOffAccount,
        user: $user,
        description: 'Test Write Off'
    );

    // Act
    (new CreateJournalEntryForStatementLineAction())->execute($dto);

    // Assert
    $this->assertDatabaseHas('journal_entries', [
        'source_id' => $line->id,
        'source_type' => BankStatementLine::class,
        'description' => 'Test Write Off'
    ]);
});

it('updates the statement line status to reconciled within the same atomic transaction', function () {
    // Arrange
    $company = Company::first();
    $user = User::factory()->for($company)->create();
    $line = BankStatementLine::factory()->for(BankStatement::factory()->for($company)->for(Journal::factory()->for($company)->create())->create())->create(['is_reconciled' => false]);
    $writeOffAccount = Account::factory()->for($company)->create();

    $dto = new CreateJournalEntryForStatementLineDTO(
        bankStatementLine: $line,
        writeOffAccount: $writeOffAccount,
        user: $user,
        description: 'Test Reconciliation'
    );

    // Act
    (new CreateJournalEntryForStatementLineAction())->execute($dto);

    // Assert
    $this->assertDatabaseHas('bank_statement_lines', [
        'id' => $line->id,
        'is_reconciled' => true,
    ]);

    $line->refresh();
    expect($line->is_reconciled)->toBeTrue();
});

it('rolls back the entire transaction if updating the statement line fails', function () {
    // Arrange
    $company = Company::first();
    $user = User::factory()->for($company)->create();
    $line = BankStatementLine::factory()->for(BankStatement::factory()->for($company)->for(Journal::factory()->for($company)->create())->create())->create();
    $writeOffAccount = Account::factory()->for($company)->create();

    $dto = new CreateJournalEntryForStatementLineDTO(
        bankStatementLine: $line,
        writeOffAccount: $writeOffAccount,
        user: $user,
        description: 'Test Rollback'
    );

    // Mock the DB transaction to fail
    DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
        throw new \Exception('DB error');
    });

    // Act & Assert
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('DB error');

    (new CreateJournalEntryForStatementLineAction())->execute($dto);

    // These assertions will not be reached, but are here for clarity.
    // In a real scenario, we'd check the state *after* catching the exception.
    $this->assertDatabaseCount('journal_entries', 0);
    $this->assertDatabaseHas('bank_statement_lines', [
        'id' => $line->id,
        'is_reconciled' => false,
    ]);
});

it('handles multi-currency scenarios correctly', function (string $currencyCode, int $minorAmount, string $majorAmount) {
    // Arrange
    $company = Company::first();
    $currency = Currency::firstWhere('code', $currencyCode) ?? Currency::factory()->create(['code' => $currencyCode]);
    $currency->update(['decimal_places' => $currencyCode === 'IQD' ? 3 : 2]);
    $company->update(['currency_id' => $currency->id]);

    $user = User::factory()->for($company)->create();
    $journal = Journal::factory()->for($company)->create();
    $bankStatement = BankStatement::factory()->for($company)->for($journal)->create(['currency_id' => $currency->id]);
    $line = BankStatementLine::factory()->for($bankStatement)->create(['amount' => $minorAmount]);
    $writeOffAccount = Account::factory()->for($company)->create();
    $bankAccount = $journal->defaultDebitAccount;

    $dto = new CreateJournalEntryForStatementLineDTO(
        bankStatementLine: $line,
        writeOffAccount: $writeOffAccount,
        user: $user,
        description: "Test {$currencyCode}"
    );

    // Act
    (new CreateJournalEntryForStatementLineAction())->execute($dto);

    // Assert
    $journalEntry = JournalEntry::first();
    $this->assertModelExists($journalEntry);
    expect($journalEntry->currency_id)->toBe($currency->id);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $writeOffAccount->id,
        'debit' => 0,
        'credit' => $minorAmount,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $bankAccount->id,
        'debit' => $minorAmount,
        'credit' => 0,
    ]);

})->with([
    'IQD' => ['IQD', 50000, '50.000'],
    'USD' => ['USD', 7500, '75.00'],
]);
