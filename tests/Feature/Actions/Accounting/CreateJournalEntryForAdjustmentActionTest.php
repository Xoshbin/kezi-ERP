<?php

use App\Models\User;
use Brick\Money\Money;
use App\Models\AdjustmentDocument;
use Tests\Traits\CreatesApplication;
use Tests\Traits\WithConfiguredCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Actions\Accounting\CreateJournalEntryForAdjustmentAction;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

test('it creates a correct journal entry for a posted adjustment document (credit note)', function () {

    $adjustment = AdjustmentDocument::factory()
        ->for($this->company)
        ->create([
            'currency_id' => $this->company->currency_id,
            'total_amount' => Money::of(110, $this->company->currency->code),
            'total_tax' => Money::of(10, $this->company->currency->code),
            'posted_at' => now(),
            'reference_number' => 'TEST-CN-001',
        ]);

    // 2. Act
    $action = app(CreateJournalEntryForAdjustmentAction::class);
    $journalEntry = $action->execute($adjustment, $this->user);

    // 3. Assert
    $this->assertNotNull($journalEntry);
    $this->assertTrue($journalEntry->is_posted);
    $this->assertEquals($this->company->default_sales_journal_id, $journalEntry->journal_id);

    // Assert correct totals
    $expectedTotal = Money::of(110, $this->company->currency->code);
    $this->assertTrue($journalEntry->total_debit->isEqualTo($expectedTotal));
    $this->assertTrue($journalEntry->total_credit->isEqualTo($expectedTotal));

    // Assert correct accounts were used for the reversing entry
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_sales_discount_account_id,
        'debit' => 100000, // Subtotal (110 - 10)
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_tax_account_id,
        'debit' => 10000, // Tax
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_accounts_receivable_id,
        'credit' => 110000, // Total
    ]);
});
