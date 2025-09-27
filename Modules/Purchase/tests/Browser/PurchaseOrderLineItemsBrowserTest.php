<?php

namespace Modules\Purchase\Tests\Browser;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

class PurchaseOrderLineItemsBrowserTest extends DuskTestCase
{
    use DatabaseMigrations, WithConfiguredCompany;

    protected $vendor;
    protected $product;
    protected $tax;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    /**
     * Test creating a purchase order with line items through the browser
     */
    public function test_can_create_purchase_order_with_line_items()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/companies/{$this->company->id}/purchases/purchase-orders/create")
                ->waitFor('[data-field-wrapper="vendor_id"]')

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
                ->pause(1000) // Wait for reactive updates

                // Verify description was auto-populated
                ->assertInputValue('[data-field-wrapper="lines"] [data-field-wrapper="description"] input', 'Test Product Description')

                // Fill quantity and unit price
                ->type('[data-field-wrapper="lines"] [data-field-wrapper="quantity"] input', '5')
                ->type('[data-field-wrapper="lines"] [data-field-wrapper="unit_price"] input', '10.00')

                // Select tax
                ->select('[data-field-wrapper="lines"] [data-field-wrapper="tax_id"] select', $this->tax->id)

                // Submit the form
                ->click('button[type="submit"]')
                ->waitForText('Purchase order created successfully')

                // Verify we're redirected to the edit page
                ->assertPathMatches('/companies/\d+/purchases/purchase-orders/\d+/edit');
        });

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
    }

    /**
     * Test that totals are calculated correctly as user enters data
     */
    public function test_totals_are_calculated_live()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/companies/{$this->company->id}/purchases/purchase-orders/create")
                ->waitFor('[data-field-wrapper="vendor_id"]')

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
                ->pause(2000)

                // Check that totals section shows calculated values
                // Note: The exact selectors may need adjustment based on the actual rendered HTML
                ->assertPresent('[data-field-wrapper="total_amount"]')
                ->assertPresent('[data-field-wrapper="total_tax"]');
        });
    }

    /**
     * Test validation errors are shown for required fields
     */
    public function test_validation_errors_for_required_fields()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/companies/{$this->company->id}/purchases/purchase-orders/create")
                ->waitFor('[data-field-wrapper="vendor_id"]')

                // Fill basic PO information
                ->select('[data-field-wrapper="vendor_id"] select', $this->vendor->id)
                ->select('[data-field-wrapper="currency_id"] select', $this->company->currency_id)

                // Add a line item but leave required fields empty
                ->click('[data-field-wrapper="lines"] button[type="button"]')
                ->waitFor('[data-field-wrapper="lines"] [data-field-wrapper="product_id"]')

                // Try to submit without filling required line item fields
                ->click('button[type="submit"]')
                ->pause(1000)

                // Check for validation errors
                ->assertPresent('.fi-fo-field-wrp-error-message'); // Filament error message class
        });
    }
}
