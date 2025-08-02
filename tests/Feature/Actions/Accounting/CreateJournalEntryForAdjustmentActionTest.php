<?php

use App\Actions\Accounting\CreateJournalEntryForAdjustmentAction;
use App\Models\AdjustmentDocument;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

test('it creates a correct journal entry for a posted adjustment document (credit note)', function () {
    // 1. Arrange
    $company = $this->createConfiguredCompany();
    $user = User::factory()->for($company)->create();
    $currencyCode = $company->currency->code;

    $adjustment = AdjustmentDocument::factory()
        ->for($company)
        ->create([
            'total_amount' => Money::of(110, $currencyCode),
            'total_tax' => Money::of(10, $currencyCode),
            'posted_at' => now(),
            'reference_number' => 'TEST-CN-001',
        ]);

    // 2. Act
    $action = app(CreateJournalEntryForAdjustmentAction::class);
    $journalEntry = $action->execute($adjustment, $user);

    // 3. Assert
    $this->assertNotNull($journalEntry);
    $this->assertTrue($journalEntry->is_posted);
    $this->assertEquals($company->default_sales_journal_id, $journalEntry->journal_id);

    // Assert correct totals
    $expectedTotal = Money::of(110, $currencyCode);
    $this->assertTrue($journalEntry->total_debit->isEqualTo($expectedTotal));
    $this->assertTrue($journalEntry->total_credit->isEqualTo($expectedTotal));

    // Assert correct accounts were used for the reversing entry
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $company->default_sales_discount_account_id,
        'debit' => 100000, // Subtotal (110 - 10)
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $company->default_tax_account_id,
        'debit' => 10000, // Tax
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $company->default_accounts_receivable_id,
        'credit' => 110000, // Total
    ]);
});
