<?php

use Brick\Money\Money;
use Modules\Accounting\Models\Tax;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);
    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Product',
        'description' => 'Test Product Description',
        'unit_price' => Money::of(1000, $this->company->currency->code), // $10.00
    ]);
    $this->tax = Tax::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'VAT 10%',
        'rate' => 10.0,
        'is_active' => true,
    ]);
});

test('can create purchase order with line items', function () {
    // Note: loginAs might need UI login if not supported by the driver directly,
    // but assuming standard Laravel test helpers might work or we use browser->loginAs if using Dusk driver.
    // However, Pest Browser (Playwright) works differently.
    // We will attempt manual login via UI if loginAs is not available, but for now let's try to assume we can log in.
    // Since loginAs operates on the session, and Playwright visits a URL, we need to ensure the session cookie is set.
    // The safest way in a browser test without specific helpers is to visit the login page.

    // Changing approach to UI login to be safe unless we are sure about loginAs support.

    $page = $this->visit('/login')
        ->type('input[type="email"]', $this->user->email)
        ->type('input[type="password"]', 'password') // Assuming 'password' is the default factory password
        ->click('button[type="submit"]')
        ->waitForText('Dashboard'); // Verify login

    $page->script("window.location.href = '/companies/{$this->company->id}/purchases/purchase-orders/create'");

    $page->waitFor('[data-field-wrapper="vendor_id"]')

        // Fill basic PO information
        ->select('[data-field-wrapper="vendor_id"] select', $this->vendor->id)
        ->select('[data-field-wrapper="currency_id"] select', $this->company->currency_id)
        ->type('[data-field-wrapper="reference"] input', 'TEST-REF-001')
        ->type('[data-field-wrapper="notes"] textarea', 'Test purchase order with line items')

        // Add a line item
        ->click('[data-field-wrapper="lines"] button[type="button"]') // Add item button
        ->waitFor('[data-field-wrapper="lines"] [data-field-wrapper="product_id"]')

        // Select product and verify auto-population
        ->select('[data-field-wrapper="lines"] [data-field-wrapper="product_id"] select', $this->product->id)
        // ->pause(1000) // Replaced with waitFor if possible, or keep pause if explicitly needed for reactivity
        ->wait(1000)

        // Verify description was auto-populated
        ->assertValue('[data-field-wrapper="lines"] [data-field-wrapper="description"] input', 'Test Product Description')

        // Fill quantity and unit price
        ->type('[data-field-wrapper="lines"] [data-field-wrapper="quantity"] input', '5')
        ->type('[data-field-wrapper="lines"] [data-field-wrapper="unit_price"] input', '10.00')

        // Select tax
        ->select('[data-field-wrapper="lines"] [data-field-wrapper="tax_id"] select', $this->tax->id)

        // Submit the form
        ->click('button[type="submit"]')
        ->waitForText('Purchase order created successfully')

        // Verify we're redirected to the edit page
        ->assertPathMatches('/\/companies\/\d+\/purchases\/purchase-orders\/\d+\/edit/');

    // Verify the purchase order was created in the database
    $this->assertDatabaseHas('purchase_orders', [
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'reference' => 'TEST-REF-001',
    ]);

    // Verify the line item was created
    $this->assertDatabaseHas('purchase_order_lines', [
        'product_id' => $this->product->id,
        'description' => 'Test Product Description',
        'quantity' => 5,
        'tax_id' => $this->tax->id,
    ]);
});

test('totals are calculated live', function () {
    $page = $this->visit('/login')
        ->type('input[type="email"]', $this->user->email)
        ->type('input[type="password"]', 'password')
        ->click('button[type="submit"]')
        ->waitForText('Dashboard');

    $page->script("window.location.href = '/companies/{$this->company->id}/purchases/purchase-orders/create'");

    $page->waitFor('[data-field-wrapper="vendor_id"]')

        // Fill basic PO information
        ->select('[data-field-wrapper="vendor_id"] select', $this->vendor->id)
        ->select('[data-field-wrapper="currency_id"] select', $this->company->currency_id)

        // Add a line item
        ->click('[data-field-wrapper="lines"] button[type="button"]')
        ->waitFor('[data-field-wrapper="lines"] [data-field-wrapper="product_id"]')

        // Fill line item data
        ->select('[data-field-wrapper="lines"] [data-field-wrapper="product_id"] select', $this->product->id)
        ->type('[data-field-wrapper="lines"] [data-field-wrapper="description"] input', 'Test Line Item')
        ->type('[data-field-wrapper="lines"] [data-field-wrapper="quantity"] input', '2')
        ->type('[data-field-wrapper="lines"] [data-field-wrapper="unit_price"] input', '25.00')
        ->select('[data-field-wrapper="lines"] [data-field-wrapper="tax_id"] select', $this->tax->id)

        // Wait for calculations to update
        ->wait(2000)

        // Check that totals section shows calculated values
        ->assertPresent('[data-field-wrapper="total_amount"]')
        ->assertPresent('[data-field-wrapper="total_tax"]');
});

test('validation errors for required fields', function () {
    $page = $this->visit('/login')
        ->type('input[type="email"]', $this->user->email)
        ->type('input[type="password"]', 'password')
        ->click('button[type="submit"]')
        ->waitForText('Dashboard');

    $page->script("window.location.href = '/companies/{$this->company->id}/purchases/purchase-orders/create'");

    $page->waitFor('[data-field-wrapper="vendor_id"]')

        // Fill basic PO information
        ->select('[data-field-wrapper="vendor_id"] select', $this->vendor->id)
        ->select('[data-field-wrapper="currency_id"] select', $this->company->currency_id)

        // Add a line item but leave required fields empty
        ->click('[data-field-wrapper="lines"] button[type="button"]')
        ->waitFor('[data-field-wrapper="lines"] [data-field-wrapper="product_id"]')

        // Try to submit without filling required line item fields
        ->click('button[type="submit"]')
        ->wait(1000)

        // Check for validation errors
        ->assertPresent('.fi-fo-field-wrp-error-message'); // Filament error message class
});
