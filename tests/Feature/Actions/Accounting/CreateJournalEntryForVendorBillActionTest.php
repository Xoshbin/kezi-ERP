<?php

use App\Actions\Accounting\CreateJournalEntryForVendorBillAction;
use App\Models\Product;
use App\Models\Tax;
use App\Models\User;
use App\Models\VendorBill;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

test('it creates a correct journal entry for a posted vendor bill', function () {
    // 1. Arrange
    $company = $this->createConfiguredCompany();
    $user = User::factory()->for($company)->create();
    $currencyCode = $company->currency->code;

    $tax = Tax::factory()->for($company)->create([
        'rate' => 0.10,
    ]);
    $product = Product::factory()->for($company)->create();

    $vendorBill = VendorBill::factory()->for($company)->create([
        'status' => VendorBill::STATUS_POSTED,
        'posted_at' => now(),
    ]);
    $vendorBill->lines()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'description' => 'asdf',
        'unit_price' => Money::of(500, $currencyCode),
        'tax_id' => $tax->id,
        'subtotal' => Money::of(500, $currencyCode),
        'total_line_tax' => Money::of(50, $currencyCode),
        'expense_account_id' => $product->expense_account_id,
    ]);

    // --- FIX IS HERE ---

    // Re-fetch a fresh instance of the model from the database to ensure we have
    // the updated totals before passing the model to the action.
    $freshVendorBill = VendorBill::find($vendorBill->id);

    // 2. Act
    $action = new CreateJournalEntryForVendorBillAction();
    // Pass the fresh, correct model to the action.
    $journalEntry = $action->execute($freshVendorBill, $user);

    // 3. Assert (This will now pass)
    $this->assertNotNull($journalEntry);
    $this->assertTrue($journalEntry->is_posted);
    $this->assertEquals($company->default_purchase_journal_id, $journalEntry->journal_id);

    $expectedTotal = Money::of(550, $currencyCode);
    $this->assertTrue($journalEntry->total_credit->isEqualTo($expectedTotal));

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $company->default_accounts_payable_id,
        'credit' => 550000,
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $product->expense_account_id,
        'debit' => 500000,
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $company->default_tax_receivable_id,
        'debit' => 50000,
    ]);
});
