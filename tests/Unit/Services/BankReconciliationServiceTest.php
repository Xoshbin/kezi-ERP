<?php

use App\Models\Account;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Payment;
use App\Models\User;
use App\Services\BankReconciliationService;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Currency;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Setup a default company, user, and journal for all tests
beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->bankJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'Bank',
    ]);
});

it('throws an exception if the company is missing default accounts', function () {
    // Arrange
    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $this->bankJournal->id,
        'status' => Payment::STATUS_CONFIRMED,
    ]);

    $service = new BankReconciliationService();

    // Act & Assert
    expect(fn() => $service->reconcile([], [$payment->id], $this->user))
        ->toThrow(RuntimeException::class, "Company '{$this->company->name}' is missing default bank or outstanding accounts configuration.");
});

it('successfully reconciles a payment and a bank statement line', function () {
    // Arrange
    $this->company->update([
        'default_bank_account_id' => Account::factory()->create(['company_id' => $this->company->id])->id,
        'default_outstanding_receipts_account_id' => Account::factory()->create(['company_id' => $this->company->id])->id,
    ]);

    $currency = Currency::factory()->create(['code' => 'USD']);

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
        'status' => Payment::STATUS_CONFIRMED, // <-- FIX WAS HERE
        'amount' => Money::of(1000, 'USD'),
        'payment_type' => 'inbound',
    ]);

    $service = new BankReconciliationService();

    // Act
    $service->reconcile([$statementLine->id], [$payment->id], $this->user);

    // Assert
    $this->assertDatabaseHas('bank_statement_lines', [ // <-- Add $this->
        'id' => $statementLine->id,
        'is_reconciled' => true,
    ]);

    $this->assertDatabaseHas('payments', [ // <-- Add $this->
        'id' => $payment->id,
        'status' => Payment::STATUS_RECONCILED,
    ]);

    $this->assertDatabaseHas('journal_entries', [ // <-- Add $this->
        'source_type' => Payment::class,
        'source_id' => $payment->id,
        'description' => 'Reconciliation for Payment #' . $payment->id,
    ]);
});
