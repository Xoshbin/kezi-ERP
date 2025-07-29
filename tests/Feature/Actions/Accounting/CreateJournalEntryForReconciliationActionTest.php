<?php

use App\Actions\Accounting\CreateJournalEntryForReconciliationAction;
use App\Models\Payment;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

test('it creates a correct journal entry for a payment reconciliation', function () {
    // 1. Arrange
    $company = $this->createConfiguredCompany();
    $user = User::factory()->for($company)->create();
    $currencyCode = $company->currency->code;

    $payment = Payment::factory()
        ->for($company)
        ->create([
            'amount' => Money::of(250, $currencyCode),
            'journal_id' => $company->default_bank_journal_id,
        ]);

    // 2. Act
    $action = new CreateJournalEntryForReconciliationAction();
    $journalEntry = $action->execute($payment, $user);

    // 3. Assert
    $this->assertNotNull($journalEntry);
    $this->assertTrue($journalEntry->is_posted);

    // Assert correct totals
    $expectedTotal = Money::of(250, $currencyCode);
    $this->assertTrue($journalEntry->total_debit->isEqualTo($expectedTotal));
    $this->assertTrue($journalEntry->total_credit->isEqualTo($expectedTotal));

    // Assert correct accounts were used for the reconciliation entry
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $company->default_bank_account_id,
        'debit' => 250000,
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $company->default_outstanding_receipts_account_id,
        'credit' => 250000,
    ]);
});
