<?php

use Brick\Money\Money;
use App\Models\Payment;
use Tests\Traits\WithConfiguredCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Actions\Accounting\CreateJournalEntryForReconciliationAction;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

test('it creates a correct journal entry for a payment reconciliation', function () {

    $payment = Payment::factory()
        ->for($this->company)
        ->create([
            'amount' => Money::of(250, $$this->company->currencyCode),
            'journal_id' => $this->company->default_bank_journal_id,
        ]);

    // 2. Act
    $action = app(CreateJournalEntryForReconciliationAction::class);
    $journalEntry = $action->execute($payment, $this->user);

    // 3. Assert
    $this->assertNotNull($journalEntry);
    $this->assertTrue($journalEntry->is_posted);

    // Assert correct totals
    $expectedTotal = Money::of(250, $$this->company->currencyCode);
    $this->assertTrue($journalEntry->total_debit->isEqualTo($expectedTotal));
    $this->assertTrue($journalEntry->total_credit->isEqualTo($expectedTotal));

    // Assert correct accounts were used for the reconciliation entry
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_bank_account_id,
        'debit' => 250000,
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_outstanding_receipts_account_id,
        'credit' => 250000,
    ]);
});
