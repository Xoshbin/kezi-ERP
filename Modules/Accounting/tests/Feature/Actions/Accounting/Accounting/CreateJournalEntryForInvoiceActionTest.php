<?php

use App\Actions\Accounting\CreateJournalEntryForInvoiceAction;
use App\Enums\Sales\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Tax;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

test('it creates a correct journal entry for a posted invoice', function () {

    $tax = Tax::factory()->for($this->company)->create([
        'rate' => 0.10, // 10% tax
        'tax_account_id' => $this->company->default_tax_account_id,
    ]);
    $product = \Modules\Product\Models\Product::factory()->for($this->company)->create([
        'income_account_id' => \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => 'income'])->id,
    ]);

    $invoice = \Modules\Sales\Models\Invoice::factory()->for($this->company)->create([
        'status' => InvoiceStatus::Posted,
        'posted_at' => now(),
        'invoice_number' => 'TEST-INV-001',
    ]);
    $unitPrice = Money::of(100, $this->company->currency->code);
    $subtotal = $unitPrice->multipliedBy(2);
    $taxAmount = $subtotal->multipliedBy($tax->rate);

    $invoice->invoiceLines()->create([
        'company_id' => $invoice->company_id,
        'product_id' => $product->id,
        'income_account_id' => $product->income_account_id,
        'description' => 'Test Product',
        'quantity' => 2,
        'unit_price' => $unitPrice,
        'tax_id' => $tax->id,
        'subtotal' => $subtotal,
        'total_line_tax' => $taxAmount,
    ]);

    // THE FIX: Refresh the invoice to get the totals calculated automatically by the observer.
    $invoice->refresh();

    // 2. Act
    $action = app(CreateJournalEntryForInvoiceAction::class);
    $journalEntry = $action->execute($invoice, $this->user);

    // 3. Assert (This will now pass)
    $this->assertNotNull($journalEntry);
    $this->assertTrue($journalEntry->is_posted);
    $this->assertEquals($this->company->default_sales_journal_id, $journalEntry->journal_id);

    $expectedTotal = Money::of(220, $this->company->currency->code); // 200 (subtotal) + 20 (tax)
    expect($journalEntry->total_debit->isEqualTo($expectedTotal))->toBeTrue();

    // Assert correct accounts were used
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_accounts_receivable_id,
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
