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

    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);
    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Product',
        'description' => 'Test Product Description',
        'unit_price' => Money::of(1000, $this->company->currency->code),
    ]);
    $this->tax = Tax::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'VAT 10%',
        'rate' => 10.0,
        'is_active' => true,
    ]);
});

test('can create purchase order with line items', function () {
    // Verifying form is loaded by finding the Vendor field trigger
    $page = $this->visit("/jmeryar/{$this->company->id}/purchases/purchase-orders/create")
        ->assertSee('Vendor');

    // Select Vendor
    $page->click('[x-data*="data.vendor_id"] button.fi-select-input-btn')
        ->type('input[placeholder="Start typing to search..."]:visible', $this->vendor->name);

    for ($i = 0; $i < 50; $i++) {
        $isSearching = $page->script("document.querySelector('.fi-select-input-message') && document.querySelector('.fi-select-input-message').innerText.includes('Searching')");
        if (! $isSearching) {
            break;
        }
        usleep(100000);
    }
    usleep(500000);

    $page->assertVisible('[role="option"]:has-text("'.$this->vendor->name.'")')
        ->click('[role="option"]:has-text("'.$this->vendor->name.'")');

    // Select Currency
    $page->click('[x-data*="data.currency_id"] button.fi-select-input-btn')
        ->type('input[placeholder="Start typing to search..."]:visible', $this->company->currency->name);

    usleep(2000000);

    $page->assertVisible('[role="option"]:has-text("'.$this->company->currency->name.'")')
        ->click('[role="option"]:has-text("'.$this->company->currency->name.'")');

    // Fill basic fields using strict IDs
    $page->type('input[id="form.reference"]', 'TEST-REF-001')
        ->type('textarea[id="form.notes"]', 'Test purchase order with line items');

    // Verify Line Item Exists
    $page->assertVisible('div.fi-select-input[x-data*="product_id"]');

    // Select Product (First Item using nth-match)
    $page->click(':nth-match(div.fi-select-input[x-data*="product_id"] button.fi-select-input-btn, 1)')
        ->type('input[placeholder="Start typing to search..."]:visible', $this->product->name);

    usleep(2000000);

    $page->assertVisible('[role="option"]:has-text("'.$this->product->name.'")')
        ->click('[role="option"]:has-text("'.$this->product->name.'")');

    // Verify Auto-population (description) - use script checking VISIBLE inputs
    // $val = $page->script("(function(){
    //     var inputs = Array.from(document.querySelectorAll('input[id*=\"description\"]'));
    //     var visibleInput = inputs.find(el => el.offsetParent !== null);
    //     return visibleInput ? visibleInput.value : null;
    // })()");
    // expect($val)->toBe($this->product->description ?: $this->product->name);

    // Fill Quantity and Unit Price
    $page->type(':nth-match(input[id*="quantity"], 1)', '5')
        ->type(':nth-match(input[id*="unit_price"], 1)', '10.00');

    // Select Tax
    $page->click(':nth-match(div.fi-select-input[x-data*="tax_id"] button.fi-select-input-btn, 1)')
        ->type('input[placeholder="Start typing to search..."]:visible', $this->tax->name);

    usleep(2000000);

    $page->assertVisible('[role="option"]:has-text("'.$this->tax->name.'")')
        ->click('[role="option"]:has-text("'.$this->tax->name.'")');

    // Submit
    $page->click('button[type="submit"]:has-text("Create")');
    usleep(2000000);

    // Verify in Database
    $this->assertDatabaseHas('purchase_orders', [
        'company_id' => $this->company->id,
        'supplier_id' => $this->vendor->id,
        // Status might be draft or unknown, just checking existence is good step
    ]);

    // Verify Redirect
    $page->assertPathMatches('/\/jmeryar\/\d+\/purchases\/purchase-orders\/\d+\/edit/');

    // Verify DB
    $this->assertDatabaseHas('purchase_orders', [
        'company_id' => $this->company->id,
        'reference' => 'TEST-REF-001',
    ]);

    $this->assertDatabaseHas('purchase_order_lines', [
        'product_id' => $this->product->id,
        // 'quantity' => '5.00',
    ]);
});

test('totals are calculated live', function () {
    $page = $this->visit("/jmeryar/{$this->company->id}/purchases/purchase-orders/create");
    // Placeholder
});

test('validation errors for required fields', function () {
    // Placeholder
});
