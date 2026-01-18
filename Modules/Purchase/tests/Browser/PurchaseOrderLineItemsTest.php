<?php

use Brick\Money\Money;
use Modules\Accounting\Models\Tax;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    // Ensure migrations are run for the shared sqlite file
    \Illuminate\Support\Facades\Artisan::call('migrate:fresh');

    // Manual connection to Playwright
    \Pest\Browser\ServerManager::instance()->playwright()->start();
    \Pest\Browser\Playwright\Client::instance()->connectTo(
        \Pest\Browser\ServerManager::instance()->playwright()->url()
    );
    \Pest\Browser\ServerManager::instance()->http()->bootstrap();

    $this->setupWithConfiguredCompany();

    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);

    // Debug: Ensure data is in DB for test process
    $this->assertDatabaseHas('partners', ['id' => $this->vendor->id]);

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
        ->waitForText('Vendor');

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

    $page->assertVisible('[role="option"]:has-text("'.$this->vendor->name.'"):visible')
        ->click('[role="option"]:has-text("'.$this->vendor->name.'"):visible');

    // Select Currency
    $page->click('[x-data*="currency_id"] button.fi-select-input-btn')
        ->type('input[placeholder="Start typing to search..."]:visible', $this->company->currency->name);

    usleep(2000000);

    $page->assertVisible('[role="option"]:has-text("'.$this->company->currency->name.'"):visible')
        ->click('[role="option"]:has-text("'.$this->company->currency->name.'"):visible');

    // Fill basic fields using strict IDs
    $page->type('input[id="form.reference"]', 'TEST-REF-001')
        ->type('textarea[id="form.notes"]', 'Test purchase order with line items');

    // Add line item - Removed because minItems(1) creates default row
    // $page->click('button:has-text("Add to line items")');
    // usleep(1000000);

    // Verify Line Item Exists
    $page->assertVisible('div.fi-select-input[x-data*="product_id"]');

    // Select Product
    $page->click(':nth-match([x-data*="product_id"] button.fi-select-input-btn, 1)')
        ->type('input[placeholder="Start typing to search..."]:visible', $this->product->name);

    usleep(2000000);

    $page->assertVisible('[role="option"]:has-text("'.$this->product->name.'"):visible')
        ->click('[role="option"]:has-text("'.$this->product->name.'"):visible');

    // Wait for Livewire to populate description
    usleep(3000000);

    // Verify Default Quantity
    $page->assertValue(':nth-match(input[id*="quantity"], 1)', '1');

    // Fill Quantity and Unit Price
    // Note: typing triggers necessary Livewire/Alpine events even if value remains default due to masking/validation delays.
    $page->type(':nth-match(input[id*="quantity"], 1)', '5')
        ->type(':nth-match(input[id*="unit_price"], 1)', '10.00');

    // Select Tax
    $page->click(':nth-match(div.fi-select-input[x-data*="tax_id"] button.fi-select-input-btn, 1)')
        ->type('input[placeholder="Start typing to search..."]:visible', $this->tax->name);

    usleep(4000000);

    $page->assertVisible('[role="option"]:has-text("'.$this->tax->name.'"):visible')
        ->click('[role="option"]:has-text("'.$this->tax->name.'"):visible');

    // Submit
    // Verify Vendor exists in DB before submitting
    $this->assertDatabaseHas('partners', ['id' => $this->vendor->id]);

    // Use vanilla JS to find and click the button correctly
    $page->script(<<<'JS'
        (function() {
            const buttons = Array.from(document.querySelectorAll('button'));
            const createBtn = buttons.find(b => (b.innerText.includes('Create') || b.textContent.includes('Create')) && b.classList.contains('fi-color-primary'));
            if (createBtn) {
                createBtn.click();
            }
        })()
    JS);

    // Wait for the Edit page to load by looking for the header
    $page->waitForText('Edit Purchase Order', 30);

    // Now check the URL
    $url = $page->url();
    $this->assertMatchesRegularExpression('/\/jmeryar\/\d+\/purchases\/purchase-orders\/\d+\/edit/', $url);
});

test('totals are calculated live', function () {
    $page = $this->visit("/jmeryar/{$this->company->id}/purchases/purchase-orders/create");
    // Placeholder
});

test('validation errors for required fields', function () {
    // Placeholder
});
