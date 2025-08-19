<?php

use App\Models\User;
use Brick\Money\Money;
use App\Enums\Accounting\JournalType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Payment;
use App\Models\Currency;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Enums\Payments\PaymentStatus;
use Illuminate\Support\Facades\Log;
use App\Services\BankReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Setup a default company, user, and journal for all tests
beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->bankJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => JournalType::Bank,
    ]);
});

it('throws an exception if the company is missing default accounts', function () {
    // Arrange - Enable reconciliation so we can test the account configuration logic
    $this->company->update(['enable_reconciliation' => true]);

    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $this->bankJournal->id,
        'status' => PaymentStatus::Confirmed,
    ]);

    $service = app(BankReconciliationService::class);

    // Act & Assert
    expect(fn() => $service->reconcile([], [$payment->id], $this->user))
        ->toThrow(RuntimeException::class, "Company '{$this->company->name}' is missing default bank or outstanding accounts configuration.");
});

it('successfully reconciles a payment and a bank statement line', function () {
    // Arrange - Enable reconciliation and configure default accounts
    $this->company->update([
        'enable_reconciliation' => true,
        'default_bank_account_id' => Account::factory()->create(['company_id' => $this->company->id])->id,
        'default_outstanding_receipts_account_id' => Account::factory()->create(['company_id' => $this->company->id])->id,
    ]);

    $currency = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1, 'is_active' => true, 'decimal_places' => 2]);

    $statement = BankStatement::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $this->bankJournal->id,
        'currency_id' => $currency->id,
    ]);

    $statementLine = BankStatementLine::factory()->create([
        'bank_statement_id' => $statement->id,
        'company_id' => $this->company->id,
        'is_reconciled' => false,
        'amount' => Money::of(1000, 'USD'),
    ]);

    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $this->bankJournal->id,
        'currency_id' => $currency->id,
        'status' => PaymentStatus::Confirmed, // <-- FIX WAS HERE
        'amount' => Money::of(1000, 'USD'),
        'payment_type' => 'inbound',
    ]);

    $service = app(BankReconciliationService::class);

    // Act
    $service->reconcile([$statementLine->id], [$payment->id], $this->user);

    // Assert
    $this->assertDatabaseHas('bank_statement_lines', [ // <-- Add $this->
        'id' => $statementLine->id,
        'is_reconciled' => true,
    ]);

    $this->assertDatabaseHas('payments', [ // <-- Add $this->
        'id' => $payment->id,
        'status' => PaymentStatus::Reconciled,
    ]);

    $this->assertDatabaseHas('journal_entries', [ // <-- Add $this->
        'source_type' => Payment::class,
        'source_id' => $payment->id,
        'description' => 'Reconciliation for Payment #' . $payment->id,
    ]);
});

it('creates a write-off for a single bank statement line', function () {
    // 1. Create the bank account
    $companyBankAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'bank_and_cash',
    ]);

    // 2. Create the Bank Journal and link BOTH default accounts
    $bankJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'bank',
        'default_debit_account_id' => $companyBankAccount->id,
        'default_credit_account_id' => $companyBankAccount->id,
    ]);

    // 3. Create the expense account
    $expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'expense',
    ]);

    // 4. Create the bank statement
    $currency = $this->company->currency;
    $statement = BankStatement::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $currency->id,
    ]);

    // 5. Create the bank statement line
    $bankFeeLine = BankStatementLine::factory()->create([
        'bank_statement_id' => $statement->id,
        'is_reconciled' => false,
        'amount' => Money::of(-50, $currency->code), // A $50 bank fee
    ]);

    // We use fresh() to ensure we are reading from the database.
    // getMinorAmount() gives the raw integer value (e.g., -5000).
    $valueInDb = $bankFeeLine->fresh()->amount->getMinorAmount()->toInt();
    Log::info('1. Value immediately after creation: ' . $valueInDb);

    $service = app(BankReconciliationService::class);
    $description = 'Monthly Bank Service Fee';

    // Act
    $service->createWriteOff($bankFeeLine, $expenseAccount, $this->user, $description);

    // Assert
    $this->assertDatabaseHas('bank_statement_lines', [
        'id' => $bankFeeLine->id,
        'is_reconciled' => true,
    ]);

    $this->assertDatabaseHas('journal_entries', [
        'description' => $description,
        'total_debit' => 50000, // <-- CORRECTED
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'account_id' => $expenseAccount->id,
        'debit' => 50000, // <-- CORRECTED (Expense is debited)
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'account_id' => $companyBankAccount->id,
        'credit' => 50000, // <-- CORRECTED (Bank account is credited)
    ]);
});
