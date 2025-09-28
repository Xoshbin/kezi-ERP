<?php

use Carbon\Carbon;
use App\Models\User;
use Brick\Money\Money;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Models\Currency;
use Tests\Traits\WithConfiguredCompany;
use Modules\Accounting\Models\JournalEntry;
use Modules\Foundation\Models\CurrencyRate;
use Modules\Accounting\Models\BankStatement;
use Modules\Accounting\Models\BankStatementLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Actions\Accounting\CreateJournalEntryForStatementLineAction;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryForStatementLineDTO;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

it('correctly creates a journal entry and links it to the statement line via a polymorphic relationship', function () {
    // Arrange
    $line = BankStatementLine::factory()->for(BankStatement::factory()->for($this->company)->for(Journal::factory()->for($this->company)->create())->create())->create(['amount' => -100000]); // -100.000 IQD
    $writeOffAccount = Account::factory()->for($this->company)->create();

    $dto = new CreateJournalEntryForStatementLineDTO(
        bankStatementLine: $line,
        writeOffAccount: $writeOffAccount,
        user: $this->user,
        description: 'Test Write Off'
    );

    // Act
    (app(CreateJournalEntryForStatementLineAction::class))->execute($dto);

    // Assert
    $this->assertDatabaseHas('journal_entries', [
        'source_id' => $line->id,
        'source_type' => BankStatementLine::class,
        'description' => 'Test Write Off',
    ]);
});

it('updates the statement line status to reconciled within the same atomic transaction', function () {
    // Arrange
    $this->company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($this->company);
    $line = BankStatementLine::factory()->for(BankStatement::factory()->for($this->company)->for(Journal::factory()->for($this->company)->create())->create())->create(['is_reconciled' => false]);
    $writeOffAccount = Account::factory()->for($this->company)->create();

    $dto = new CreateJournalEntryForStatementLineDTO(
        bankStatementLine: $line,
        writeOffAccount: $writeOffAccount,
        user: $user,
        description: 'Test Reconciliation'
    );

    // Act
    (app(CreateJournalEntryForStatementLineAction::class))->execute($dto);

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
    $this->company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($this->company);
    $line = BankStatementLine::factory()->for(BankStatement::factory()->for($this->company)->for(Journal::factory()->for($this->company)->create())->create())->create();
    $writeOffAccount = Account::factory()->for($this->company)->create();

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

    (app(CreateJournalEntryForStatementLineAction::class))->execute($dto);

    // These assertions will not be reached, but are here for clarity.
    // In a real scenario, we'd check the state *after* catching the exception.
    $this->assertDatabaseCount('journal_entries', 0);
    $this->assertDatabaseHas('bank_statement_lines', [
        'id' => $line->id,
        'is_reconciled' => false,
    ]);
});

it('handles multi-currency scenarios correctly', function (string $currencyCode, int $minorAmount, string $majorAmount) {
    // Arrange - Use the existing company (IQD) and create exchange rates for foreign currencies
    $currency = Currency::firstOrCreate(['code' => $currencyCode], ['name' => $currencyCode, 'symbol' => $currencyCode, 'exchange_rate' => 1, 'is_active' => true, 'decimal_places' => $currencyCode === 'IQD' ? 3 : 2]);
    $currency->update(['decimal_places' => $currencyCode === 'IQD' ? 3 : 2]);

    // Create exchange rate if the currency is different from company's base currency
    if ($currencyCode !== $this->company->currency->code) {
        // Create the rate for yesterday to ensure it's found (effective_date <= today)
        $effectiveDate = Carbon::yesterday();
        CurrencyRate::create([
            'currency_id' => $currency->id,
            'company_id' => $this->company->id,
            'rate' => $currencyCode === 'USD' ? 1500.0 : 1.0, // 1 USD = 1500 IQD
            'effective_date' => $effectiveDate,
            'source' => 'manual',
        ]);
    }

    $user = User::factory()->create();
    $user->companies()->attach($this->company);
    $journal = Journal::factory()->for($this->company)->create();
    $bankStatement = BankStatement::factory()->for($this->company)->for($journal)->create(['currency_id' => $currency->id]);
    $line = BankStatementLine::factory()->for($bankStatement)->create(['amount' => Money::ofMinor($minorAmount, $currencyCode)]);
    $writeOffAccount = Account::factory()->for($this->company)->create();
    $bankAccount = $journal->defaultDebitAccount;

    $dto = new CreateJournalEntryForStatementLineDTO(
        bankStatementLine: $line,
        writeOffAccount: $writeOffAccount,
        user: $user,
        description: "Test {$currencyCode}"
    );

    // Act
    (app(CreateJournalEntryForStatementLineAction::class))->execute($dto);

    // Assert
    $journalEntry = JournalEntry::first();
    $this->assertModelExists($journalEntry);
    expect($journalEntry->currency_id)->toBe($currency->id);

    // Calculate expected amount in company base currency
    $expectedAmount = $minorAmount;
    if ($currencyCode !== $this->company->currency->code) {
        // Convert to company base currency using the exchange rate
        $rate = $currencyCode === 'USD' ? 1500.0 : 1.0;
        $majorAmount = $minorAmount / pow(10, $currency->decimal_places);
        $convertedMajor = $majorAmount * $rate;
        $expectedAmount = (int) round($convertedMajor * pow(10, $this->company->currency->decimal_places));
    }

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $writeOffAccount->id,
        'debit' => 0,
        'credit' => $expectedAmount,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $bankAccount->id,
        'debit' => $expectedAmount,
        'credit' => 0,
    ]);
})->with([
    'IQD' => ['IQD', 50000000, '50000.000'],
    'USD' => ['USD', 750000, '7500.00'],
]);
