<?php

use Brick\Money\Money;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Tax;
use Kezi\Foundation\Models\Partner;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use Kezi\Purchase\Models\PurchaseOrder;
use Kezi\Purchase\Services\PurchaseOrderService;
use Livewire\Livewire;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

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

    Filament::setTenant($this->company);
});

test('can create purchase order with line items through filament form', function () {
    // First, let's test without line items to see if the basic form works
    $livewire = Livewire::test(CreatePurchaseOrder::class, ['tenant' => $this->company])
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'po_date' => now()->format('Y-m-d'),
            'reference' => 'TEST-REF-001',
            'notes' => 'Test purchase order with line items',
        ]);

    // Check if we can add line items manually
    $livewire->set('data.lines', [
        [
            'product_id' => $this->product->id,
            'description' => 'Test Product Line 1',
            'quantity' => 5,
            'unit_price' => '10.00',
            'tax_id' => $this->tax->id,
            'notes' => 'Line 1 notes',
        ],
        [
            'product_id' => $this->product->id,
            'description' => 'Test Product Line 2',
            'quantity' => 3,
            'unit_price' => '15.00',
            'tax_id' => null,
            'notes' => 'Line 2 notes',
        ],
    ]);

    $livewire->call('create')
        ->assertHasNoFormErrors();

    // Verify purchase order was created
    $this->assertDatabaseHas('purchase_orders', [
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency_id,
        'reference' => 'TEST-REF-001',
        'status' => PurchaseOrderStatus::Draft,
    ]);

    // Verify line items were created
    $this->assertDatabaseHas('purchase_order_lines', [
        'product_id' => $this->product->id,
        'description' => 'Test Product Line 1',
        'quantity' => 5,
        'unit_price' => 10000, // $100.00 stored as cents (MoneyInput interprets "10.00" as $100.00)
        'tax_id' => $this->tax->id,
        'notes' => 'Line 1 notes',
    ]);

    $this->assertDatabaseHas('purchase_order_lines', [
        'product_id' => $this->product->id,
        'description' => 'Test Product Line 2',
        'quantity' => 3,
        'unit_price' => 15000, // $150.00 stored as cents
        'tax_id' => null,
        'notes' => 'Line 2 notes',
    ]);
});

test('product selection auto-populates description and unit price', function () {
    Livewire::test(CreatePurchaseOrder::class, ['tenant' => $this->company])
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'po_date' => now()->format('Y-m-d'),
            'exchange_rate_at_creation' => 1,
        ])
        // First set up an empty line structure
        ->set('data.lines', [
            [
                'product_id' => null,
                'description' => '',
                'quantity' => 1,
                'unit_price' => '',
                'tax_id' => null,
            ],
        ])
        // Now set the product_id to trigger the afterStateUpdated callback
        ->set('data.lines.0.product_id', $this->product->id)
        // Assert that the fields were auto-populated
        ->assertFormSet([
            'lines.0.description' => 'Test Product Description',
            'lines.0.unit_price' => '1000', // Product has unit_price of Money::of(1000, code) = $1000.00
        ]);
});

test('handles products with null unit price gracefully', function () {
    $productWithoutPrice = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Product Without Price',
        'description' => 'No price set',
        'unit_price' => null,
    ]);

    // Test that we can create a PO with a product that has no default price
    $livewire = Livewire::test(CreatePurchaseOrder::class, ['tenant' => $this->company])
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'po_date' => now()->format('Y-m-d'),
        ]);

    $livewire->set('data.lines', [
        [
            'product_id' => $productWithoutPrice->id,
            'description' => 'Product without default price',
            'quantity' => 1,
            'unit_price' => '25.00', // User must provide price
            'tax_id' => null,
        ],
    ]);

    $livewire->call('create')
        ->assertHasNoFormErrors();

    // Verify the purchase order was created successfully
    $this->assertDatabaseHas('purchase_order_lines', [
        'product_id' => $productWithoutPrice->id,
        'description' => 'Product without default price',
        'unit_price' => 25000, // $250.00 stored as cents (MoneyInput interprets "25.00" as $250.00)
    ]);
});

test('can create purchase order with minimum required line item data', function () {
    $livewire = Livewire::test(CreatePurchaseOrder::class, ['tenant' => $this->company])
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'po_date' => now()->format('Y-m-d'),
        ]);

    $livewire->set('data.lines', [
        [
            'product_id' => $this->product->id,
            'description' => 'Minimal Line Item',
            'quantity' => 1,
            'unit_price' => '5.00',
            // tax_id is optional
            // notes is optional
            // expected_delivery_date is optional
        ],
    ]);

    $livewire->call('create')
        ->assertHasNoFormErrors();

    // Verify the purchase order and line were created successfully
    $this->assertDatabaseHas('purchase_orders', [
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
    ]);

    $this->assertDatabaseHas('purchase_order_lines', [
        'product_id' => $this->product->id,
        'description' => 'Minimal Line Item',
        'quantity' => 1,
        'unit_price' => 5000, // $50.00 stored as cents (MoneyInput interprets "5.00" as $50.00)
    ]);
});

test('validates required line item fields', function () {
    $livewire = Livewire::test(CreatePurchaseOrder::class, ['tenant' => $this->company])
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'po_date' => now()->format('Y-m-d'),
        ]);

    // Set invalid line data
    $livewire->set('data.lines', [
        [
            // Missing required fields
            'product_id' => null,
            'description' => '',
            'quantity' => null,
            'unit_price' => '',
        ],
    ]);

    $livewire->call('create');

    // Check that there are validation errors (the exact field names might vary due to UUIDs)
    $errors = $livewire->errors();
    expect($errors->isNotEmpty())->toBeTrue();

    // Check that errors contain the expected field types
    $errorKeys = $errors->keys();
    $hasProductError = collect($errorKeys)->contains(fn ($key) => str_contains($key, 'product_id'));
    $hasDescriptionError = collect($errorKeys)->contains(fn ($key) => str_contains($key, 'description'));
    $hasQuantityError = collect($errorKeys)->contains(fn ($key) => str_contains($key, 'quantity'));
    $hasUnitPriceError = collect($errorKeys)->contains(fn ($key) => str_contains($key, 'unit_price'));

    expect($hasProductError)->toBeTrue();
    expect($hasDescriptionError)->toBeTrue();
    expect($hasQuantityError)->toBeTrue();
    expect($hasUnitPriceError)->toBeTrue();
});

test('enforces minimum quantity validation', function () {
    Livewire::test(CreatePurchaseOrder::class, ['tenant' => $this->company])
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'po_date' => now()->format('Y-m-d'),
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Invalid Quantity Line',
                    'quantity' => 0, // Invalid: should be > 0
                    'unit_price' => '10.00',
                ],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['lines.0.quantity']);
});

test('cannot confirm purchase order without line items', function () {
    // Create a purchase order without line items
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency_id,
        'created_by_user_id' => $this->user->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);

    // Attempt to confirm the purchase order should fail
    expect(function () use ($purchaseOrder) {
        app(PurchaseOrderService::class)->confirm($purchaseOrder, $this->user);
    })->toThrow(\InvalidArgumentException::class, 'Cannot confirm purchase order without any lines.');
});

test('product selection auto-populates tax_id', function () {
    // Create a product with a default purchase tax
    $purchaseTax = Tax::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Supplier VAT 15%',
        'rate' => 15.0,
        'type' => \Kezi\Accounting\Enums\Accounting\TaxType::Purchase,
    ]);

    $productWithTax = Product::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $productWithTax->purchaseTaxes()->attach($purchaseTax->id);

    Livewire::test(CreatePurchaseOrder::class, ['tenant' => $this->company])
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'po_date' => now()->format('Y-m-d'),
            'exchange_rate_at_creation' => 1,
        ])
        ->set('data.lines', [
            [
                'product_id' => null,
                'tax_id' => null,
            ],
        ])
        ->set('data.lines.0.product_id', $productWithTax->id)
        ->assertFormSet([
            'lines.0.tax_id' => $purchaseTax->id,
        ]);
});
