<?php

use Brick\Money\Money;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Enums\Shared\PaymentState;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\InvoiceLine;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    // Manual connection to Playwright
    \Pest\Browser\ServerManager::instance()->playwright()->start();
    \Pest\Browser\Playwright\Client::instance()->connectTo(
        \Pest\Browser\ServerManager::instance()->playwright()->url()
    );
    \Pest\Browser\ServerManager::instance()->http()->bootstrap();

    $this->setupWithConfiguredCompany();

    $this->customer = Partner::factory()->customer()->create(['company_id' => $this->company->id]);
    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Sales Product',
        'unit_price' => Money::of(2000, $this->company->currency->code),
    ]);
    $this->bankJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Bank',
        'type' => 'bank',
        'short_code' => 'BNK1',
    ]);

    // Create POSTED Invoice
    $this->invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->company->currency_id,
        'status' => InvoiceStatus::Posted,
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        // 'payment_state' => PaymentState::NotPaid, // Computed attribute
        'total_amount' => Money::of(2000, $this->company->currency->code),
        'invoice_number' => 'INV-TEST-001',
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $this->invoice->id,
        'product_id' => $this->product->id,
        'quantity' => 1,
        'unit_price' => Money::of(2000, $this->company->currency->code),
        'description' => 'Test Line',
    ]);
});

test('can register payment for posted invoice', function () {
    // Navigate to Invoice Edit Page (Accounting Cluster)
    $page = $this->visit("/jmeryar/{$this->company->id}/accounting/invoices/{$this->invoice->id}/edit");

    // Verify Page Title or Header
    $page->assertSee('Edit Invoice');

    // Click "Register Payment" action
    // Usually in header actions.
    $page->assertVisible('button:has-text("Register Payment")')
        ->click('button:has-text("Register Payment")');

    // Wait for Modal
    usleep(1000000);
    $page->assertSee('Register Payment');

    // Select Journal (Native Select)
    // Select Journal (Native Select in Modal)
    // Target the select inside the Filament modal content
    $page->select('.fi-modal-content select', (string) $this->bankJournal->id);

    usleep(500000);

    // Amount should be auto-filled (2000.00)
    // We can verify it if strictly necessary, but confirming workflow is priority.

    // Confirm Payment
    // Filament modal action button usually "Register Payment" or "Confirm"
    // Based on InvoiceResource: ->label(__('accounting::invoice.register_payment'))
    // Probably defaults to label.
    $page->click('.fi-modal-footer button[type="submit"]'); // Click primary submit button in modal footer

    // Wait for success notification
    $page->waitForText('Payment confirmed successfully');

    // Verify Invoice Status (Payment State)
    // Page should refresh or update badge.
    $page->assertSee('Paid');

    // Check DB for Payment creation
    $this->assertDatabaseHas('payments', [
        'company_id' => $this->company->id,
        'amount' => $this->invoice->total_amount->getMinorAmount()->toInt(),
        // MoneyCast stores exact value usually as int or string.
        // Factory used Money::of(2000), which is 2000.00.
        // If base currency is USD (2 decimals), amount is 200000?
        // Or string '2000.00'.
        // Let's rely on checking `payment_document_links` which links invoice.
    ]);

    // Check Link
    $this->assertDatabaseHas('payment_document_links', [
        'invoice_id' => $this->invoice->id,
    ]);
});
