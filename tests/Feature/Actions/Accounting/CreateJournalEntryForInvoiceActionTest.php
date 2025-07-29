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

    // Create a tax and a product
    $tax = Tax::factory()->for($company)->create([
        'rate' => Money::of('0.10', $currencyCode), // 10% tax
        'tax_account_id' => $company->default_tax_account_id,
    ]);
    $product = Product::factory()->for($company)->create();

    // Create a posted invoice with lines
    $invoice = Invoice::factory()->for($company)->create([
        'status' => Invoice::TYPE_POSTED,
        'posted_at' => now(),
        'invoice_number' => 'TEST-INV-001'
    ]);
    $invoice->invoiceLines()->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => Money::of(100, $currencyCode), // Subtotal: 200
        'tax_id' => $tax->id,
        'subtotal' => Money::of(200, $currencyCode),
        'total_line_tax' => Money::of(20, $currencyCode), // 10% of 200
    ]);
    $invoiceService = app(\App\Services\InvoiceService::class);
    $invoiceService->recalculateInvoiceTotals($invoice); // Total: 220

    // 2. Act
    $action = new CreateJournalEntryForInvoiceAction();
    $journalEntry = $action->execute($invoice, $user);

    // 3. Assert
    $this->assertNotNull($journalEntry);
    $this->assertTrue($journalEntry->is_posted);
    $this->assertEquals($company->default_sales_journal_id, $journalEntry->journal_id);

    // Assert correct total amount
    $expectedTotal = Money::of(220, $currencyCode);
    expect($journalEntry->total_debit->isEqualTo($expectedTotal))->toBeTrue();

    // Assert correct accounts were used
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $company->default_accounts_receivable_id,
        'debit' => 220000, // Debit A/R for the full amount
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $product->income_account_id,
        'credit' => 200000, // Credit Income for the subtotal
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $tax->tax_account_id,
        'credit' => 20000, // Credit Tax Payable for the tax amount
    ]);
});
