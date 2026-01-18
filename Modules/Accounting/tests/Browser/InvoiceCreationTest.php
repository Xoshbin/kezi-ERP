<?php

use Brick\Money\Money;
use Modules\Accounting\Models\Tax;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
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
    $this->tax = Tax::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'VAT 10%',
        'rate' => 10.0,
        'is_active' => true,
    ]);
});

test('can create and post customer invoice', function () {
    // Navigate to Create Invoice (Accounting Cluster)
    $page = $this->visit("/jmeryar/{$this->company->id}/accounting/invoices/create")
        ->assertSee('Customer');

    // Select Customer (customer_id)
    $page->click('[x-data*="data.customer_id"] button.fi-select-input-btn')
        ->type('input[placeholder="Start typing to search..."]:visible', $this->customer->name);

    // Wait for search
    for ($i = 0; $i < 50; $i++) {
        $isSearching = $page->script("document.querySelector('.fi-select-input-message') && document.querySelector('.fi-select-input-message').innerText.includes('Searching')");
        if (! $isSearching) {
            break;
        }
        usleep(100000);
    }
    usleep(500000);

    $page->assertVisible('[role="option"]:has-text("'.$this->customer->name.'")')
        ->click('[role="option"]:has-text("'.$this->customer->name.'")');

    // Fill Invoice Date (optional, default today)

    // Verify Line Item Exists (invoiceLines defaults to 1?)
    // In InvoiceResource line 289: ->minItems(1)
    // So 1 line exists.
    $page->assertVisible('div.fi-select-input[x-data*="product_id"]');

    // Select Product (First Item)
    $page->click(':nth-match(div.fi-select-input[x-data*="product_id"] button.fi-select-input-btn, 1)')
        ->type('input[placeholder="Start typing to search..."]:visible', $this->product->name);

    usleep(2000000);

    $page->assertVisible('[role="option"]:has-text("'.$this->product->name.'")')
        ->click('[role="option"]:has-text("'.$this->product->name.'")');

    // Fill Quantity
    $page->type(':nth-match(input[id*="quantity"], 1)', '2');

    // Skip auto-population check for now to focus on workflow
    // Fill Unit Price explicitly if needed, but it should auto-fill.
    // We can rely on total check later or DB check.

    // Submit (Create)
    $page->click('button[type="submit"]:has-text("Create")')
        ->assertVisible('div:has-text("Invoice created successfully")');

    // Verify Redirect to Edit
    $page->assertPathMatches('/\/jmeryar\/\d+\/accounting\/invoices\/\d+\/edit/');

    // Post/Confirm Invoice
    // "Confirm" action (InvoiceResource line 657)
    // It's in an ActionGroup usually? Or top bar?
    // InvoiceResource line 635: ActionGroup::make([View, Edit]).
    // Line 657: Action::make('confirm') ... ->requiresConfirmation() ->visible(Draft).
    // It might be in the Header widget or just an Action.
    // If it's a Header Action, it's visible on Edit page.
    $page->assertVisible('button:has-text("Confirm")')
        ->click('button:has-text("Confirm")');

    // Confirm Modal - "requiresConfirmation" implies a modal.
    usleep(1000000); // Wait for modal
    $page->click('button:has-text("Confirm")'); // Click confirm inside modal?
    // Filament v3 Confirmation modal usually has "Confirm" button.
    // Or "Submit".

    // Wait for success
    $page->assertVisible('div:has-text("Invoice confirmed successfully")');

    // Verify DB
    $this->assertDatabaseHas('invoices', [
        'company_id' => $this->company->id,
        'partner_id' => $this->customer->id,
        'status' => 'posted',
    ]);

    $this->assertDatabaseHas('invoice_lines', [
        'product_id' => $this->product->id,
        'quantity' => '2.00',
    ]);
});
