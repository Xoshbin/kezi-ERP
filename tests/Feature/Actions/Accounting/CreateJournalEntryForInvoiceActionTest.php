<?php

use App\Actions\Accounting\CreateJournalEntryForInvoiceAction;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Tax;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

test('it creates a correct journal entry for a posted invoice', function () {
    // 1. Arrange
    $company = $this->createConfiguredCompany();
    $user = User::factory()->for($company)->create();
    $currencyCode = $company->currency->code;

    $tax = Tax::factory()->for($company)->create([
        'rate' => 0.10, // 10% tax
        'tax_account_id' => $company->default_tax_account_id,
    ]);
    $product = Product::factory()->for($company)->create([
        'income_account_id' => \App\Models\Account::factory()->for($company)->create(['type' => 'Income'])->id,
    ]);

    $invoice = Invoice::factory()->for($company)->create([
        'status' => Invoice::STATUS_POSTED,
        'posted_at' => now(),
        'invoice_number' => 'TEST-INV-001'
    ]);
    $invoice->invoiceLines()->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => Money::of(100, $currencyCode),
        'tax_id' => $tax->id,
    ]);

    // THE FIX: Refresh the invoice to get the totals calculated automatically by the observer.
    $invoice->refresh();

    // 2. Act
    $action = new CreateJournalEntryForInvoiceAction();
    $journalEntry = $action->execute($invoice, $user);

    // 3. Assert (This will now pass)
    $this->assertNotNull($journalEntry);
    $this->assertTrue($journalEntry->is_posted);
    $this->assertEquals($company->default_sales_journal_id, $journalEntry->journal_id);

    $expectedTotal = Money::of(220, $currencyCode); // 200 (subtotal) + 20 (tax)
    expect($journalEntry->total_debit->isEqualTo($expectedTotal))->toBeTrue();

    // Assert correct accounts were used
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $company->default_accounts_receivable_id,
        'debit' => 220000,
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $product->income_account_id,
        'credit' => 200000,
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $tax->tax_account_id,
        'credit' => 20000,
    ]);
});
