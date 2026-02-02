<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Actions\Accounting\CreateJournalEntryForPaymentAction;
use Kezi\Payment\Enums\Payments\PaymentStatus;
use Kezi\Payment\Enums\Payments\PaymentType;
use Kezi\Payment\Models\Payment;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

test('it creates a correct journal entry for an inbound payment', function () {

    $this->company->currency->update(['exchange_rate' => 1.0]);
    $this->company->refresh();

    $payment = Payment::factory()->for($this->company)->create([
        'payment_type' => PaymentType::Inbound,
        'amount' => Money::of(500, $this->company->currency->code),
        'currency_id' => $this->company->currency_id,
        'journal_id' => $this->company->default_bank_journal_id,
        'status' => PaymentStatus::Confirmed,
    ]);

    // 2. Act
    $action = app(CreateJournalEntryForPaymentAction::class);
    $journalEntry = $action->execute($payment, $this->user);

    // 3. Assert
    $this->assertNotNull($journalEntry);
    $this->assertTrue($journalEntry->is_posted);

    // Assert correct accounts were used
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_bank_account_id, // Inbound payment DEBITS the bank
        'debit' => 500000,
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_accounts_receivable_id, // Inbound payment CREDITS A/R
        'credit' => 500000,
    ]);
});
