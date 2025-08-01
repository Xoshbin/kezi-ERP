<?php

use App\Models\Account;
use App\Models\BankStatementLine;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use App\Services\BankReconciliationService;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

beforeEach(function () {
    $this->company = $this->createConfiguredCompany();
    $this->user = User::factory()->for($this->company)->create();
    $this->actingAs($this->user);
});

test('a bank statement line can be reconciled with a payment', function () {
    // Arrange: Set up the necessary accounts and a payment.
    $bankAccount = Account::factory()->for($this->company)->create(['type' => 'Bank']);
    $this->company->update(['default_bank_account_id' => $bankAccount->id]);

    $currencyCode = $this->company->currency->code;
    $payment = Payment::factory()
        ->for($this->company)
        ->create([
            'amount' => Money::of(100, $currencyCode),
            'currency_id' => $this->company->currency_id,
            'status' => 'Confirmed'
        ]);

    // Arrange: Create a bank statement and a line that matches the payment.
    $bankStatement = \App\Models\BankStatement::factory()
        ->for($this->company)
        ->for($payment->journal) // We can reuse the payment's journal
        ->create([
            'starting_balance' => Money::of(0, $currencyCode),
            'currency_id' => $this->company->currency_id,
            'ending_balance' => Money::of(100, $currencyCode),
        ]);

    // Create the line for the new BankStatement.
    $bankStatementLine = BankStatementLine::factory()
        ->for($bankStatement)
        ->for($this->company) // THIS IS THE FIX: Explicitly set the company for the line.
        ->create([
            'amount' => Money::of(100, $currencyCode),
            'is_reconciled' => false,
        ]);

    // Act: Reconcile the statement line with the payment.
    (app(BankReconciliationService::class))->reconcilePayment($payment, $bankStatementLine, $this->user);

    // Assert: The statement line is now marked as reconciled.
    $bankStatementLine->refresh();
    expect($bankStatementLine->is_reconciled)->toBeTrue();
    expect($bankStatementLine->payment_id)->toBe($payment->id);

    // Assert: The payment is also marked as reconciled.
    $payment->refresh();
    expect($payment->status)->toBe('Reconciled');
});
