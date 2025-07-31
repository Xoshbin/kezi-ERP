<?php

use App\Actions\Accounting\CreateJournalEntryForPaymentAction;
use App\Models\Company;
use App\Models\Payment;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

test('it creates a correct journal entry for an inbound payment', function () {
    // 1. Arrange
    $company = $this->createConfiguredCompany();
    $user = User::factory()->for($company)->create();
    $currencyCode = $company->currency->code;

    $payment = Payment::factory()->for($company)->create([
        'payment_type' => Payment::TYPE_INBOUND,
        'amount' => Money::of(500, $currencyCode),
        'journal_id' => $company->default_bank_journal_id,
        'status' => 'Confirmed',
    ]);

    // 2. Act
    $action = new CreateJournalEntryForPaymentAction();
    $journalEntry = $action->execute($payment, $user);

    // 3. Assert
    $this->assertNotNull($journalEntry);
    $this->assertTrue($journalEntry->is_posted);

    // Assert correct accounts were used
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $company->default_bank_account_id, // Inbound payment DEBITS the bank
        'debit' => 500000,
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $company->default_accounts_receivable_id, // Inbound payment CREDITS A/R
        'credit' => 500000,
    ]);
});
