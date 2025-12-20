<?php

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Modules\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\PurchaseOrderResource;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);

    Filament::setTenant($this->company);
});

test('can render purchase order list page', function () {
    $this->get(PurchaseOrderResource::getUrl('index', tenant: $this->company))
        ->assertSuccessful();
});

test('can render purchase order create page', function () {
    $this->get(PurchaseOrderResource::getUrl('create', tenant: $this->company))
        ->assertSuccessful();
});

test('can create purchase order through filament', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);

    $livewire = Livewire::test(CreatePurchaseOrder::class, ['tenant' => $this->company])
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'po_date' => now()->format('Y-m-d'),
            'reference' => 'TEST-REF-001',
            'notes' => 'Test purchase order notes',
        ]);

    // Add a line item since it's now required
    $livewire->set('data.lines', [
        [
            'product_id' => $product->id,
            'description' => 'Test Product Line',
            'quantity' => 1,
            'unit_price' => '10.00',
            'tax_id' => null,
        ],
    ]);

    $livewire->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('purchase_orders', [
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency_id,
        'reference' => 'TEST-REF-001',
        'status' => PurchaseOrderStatus::Draft,
    ]);
});

test('can render purchase order edit page', function () {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency_id,
        'created_by_user_id' => $this->user->id,
    ]);

    $this->get(PurchaseOrderResource::getUrl('edit', ['record' => $purchaseOrder], tenant: $this->company))
        ->assertSuccessful();
});

test('can render purchase order view page', function () {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency_id,
        'created_by_user_id' => $this->user->id,
    ]);

    $this->get(PurchaseOrderResource::getUrl('view', ['record' => $purchaseOrder], tenant: $this->company))
        ->assertSuccessful();
});

test('can confirm purchase order through filament action', function () {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency_id,
        'created_by_user_id' => $this->user->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);

    // Add a line to the purchase order so it can be confirmed
    PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'product_id' => Product::factory()->create(['company_id' => $this->company->id])->id,
    ]);

    Livewire::test(EditPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey(), 'tenant' => $this->company])
        ->callAction('confirm')
        ->assertHasNoActionErrors();

    $purchaseOrder->refresh();
    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::ToReceive); // Auto-transitions to ToReceive after confirmation
    expect($purchaseOrder->confirmed_at)->not->toBeNull();
});

test('can cancel purchase order through filament action', function () {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency_id,
        'created_by_user_id' => $this->user->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);

    Livewire::test(EditPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey(), 'tenant' => $this->company])
        ->callAction('cancel')
        ->assertHasNoActionErrors();

    $purchaseOrder->refresh();
    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::Cancelled);
    expect($purchaseOrder->cancelled_at)->not->toBeNull();
});

test('confirm action transitions to ToReceive status', function () {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency_id,
        'created_by_user_id' => $this->user->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);

    // Add a line to the purchase order so it can be confirmed
    PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'product_id' => Product::factory()->create(['company_id' => $this->company->id])->id,
    ]);

    Livewire::test(EditPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey(), 'tenant' => $this->company])
        ->callAction('confirm')
        ->assertHasNoActionErrors();

    $purchaseOrder->refresh();

    // Status should be ToReceive after confirmation (automatic transition)
    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::ToReceive);
    expect($purchaseOrder->status)->not->toBe(PurchaseOrderStatus::PartiallyReceived);
    expect($purchaseOrder->status)->not->toBe(PurchaseOrderStatus::FullyReceived);
    expect($purchaseOrder->confirmed_at)->not->toBeNull();
});

test('dynamically updates line currency when header currency changes', function () {
    // Arrange: Create USD currency
    $usdCurrency = \Modules\Foundation\Models\Currency::firstOrCreate(
        ['code' => 'USD'],
        [
            'name' => ['en' => 'US Dollar'],
            'symbol' => '$',
            'is_active' => true,
            'decimal_places' => 2,
        ]
    );

    // Act
    $wire = Livewire::test(CreatePurchaseOrder::class, ['tenant' => $this->company])
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'po_date' => now()->format('Y-m-d'),
        ]);

    // Set a different currency
    $wire->set('data.currency_id', $usdCurrency->id);

    // Add a line item
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $wire->set('data.lines', [
        [
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10,
        ],
    ]);

    // Verify no errors when switching back and forth
    // This implicit verification checks if the paths are resolvable
    $wire->set('data.currency_id', $this->company->currency_id)
        ->assertHasNoFormErrors();

    $wire->set('data.currency_id', $usdCurrency->id)
        ->assertHasNoFormErrors();
});

test('can update purchase order lines through filament edit page', function () {
    // Create a product
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $product2 = Product::factory()->create(['company_id' => $this->company->id]);

    // Create a purchase order with a line
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency_id,
        'created_by_user_id' => $this->user->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);

    PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'unit_price' => 100,
        'description' => 'Original product line',
    ]);

    // Edit the purchase order - change quantity and add a new line
    $livewire = Livewire::test(EditPurchaseOrder::class, [
        'record' => $purchaseOrder->getRouteKey(),
        'tenant' => $this->company,
    ]);

    $livewire->set('data.lines', [
        [
            'product_id' => $product->id,
            'description' => 'Updated product line',
            'quantity' => 10, // Changed from 5 to 10
            'unit_price' => '150.00', // Changed from 100 to 150
            'tax_id' => null,
        ],
        [
            'product_id' => $product2->id,
            'description' => 'New second line',
            'quantity' => 3,
            'unit_price' => '50.00',
            'tax_id' => null,
        ],
    ]);

    $livewire->fillForm([
        'reference' => 'UPDATED-REF-001',
    ]);

    $livewire->call('save')
        ->assertHasNoFormErrors();

    // Verify the changes were saved
    $purchaseOrder->refresh();
    $purchaseOrder->load('lines');

    expect($purchaseOrder->reference)->toBe('UPDATED-REF-001');
    expect($purchaseOrder->lines)->toHaveCount(2);

    // Check the first line was updated
    $firstLine = $purchaseOrder->lines->where('product_id', $product->id)->first();
    expect($firstLine)->not->toBeNull();
    expect($firstLine->quantity)->toBe(10.0);
    expect($firstLine->description)->toBe('Updated product line');

    // Check the second line was added
    $secondLine = $purchaseOrder->lines->where('product_id', $product2->id)->first();
    expect($secondLine)->not->toBeNull();
    expect($secondLine->quantity)->toBe(3.0);
    expect($secondLine->description)->toBe('New second line');
});
