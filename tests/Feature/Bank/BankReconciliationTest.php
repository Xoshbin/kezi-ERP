<?php

use App\Models\Account;
use App\Models\BankStatementLine;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\BankReconciliationService;
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
    $payment = \App\Models\Payment::factory()->for($this->company)->create(['amount' => 100.00, 'status' => 'Confirmed']);

    // Arrange: Create a bank statement line that matches the payment.
    $bankStatementLine = BankStatementLine::factory()->for($this->company)->create([
        'amount' => 100.00,
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