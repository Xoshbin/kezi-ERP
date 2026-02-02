<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Inventory\Services\Inventory\StockQuantService;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

/**
 * @property \App\Models\Company $company
 * @property \Kezi\Accounting\Models\Account $inventoryAccount
 * @property \Kezi\Inventory\Models\StockLocation $stockLocation
 * @property \Kezi\Inventory\Models\StockLocation $vendorLocation
 */
uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();
});

test('storable product has zero quantity on hand by default', function () {
    /** @var \Tests\TestCase $this */
    /** @var Product $product */
    $product = Product::factory()->for($this->company)->create([
        'type' => ProductType::Storable,
        'default_inventory_account_id' => $this->inventoryAccount->id,
    ]);

    expect($product->quantity_on_hand)->toBe(0.0);
});

test('adjusting stock for a storable product updates quantity on hand', function () {
    /** @var \Tests\TestCase $this */
    /** @var Product $product */
    $product = Product::factory()->for($this->company)->create([
        'type' => ProductType::Storable,
        'default_inventory_account_id' => $this->inventoryAccount->id,
    ]);

    $stockQuantService = app(StockQuantService::class);

    // Act - Adjust stock (e.g., initial stock)
    $stockQuantService->adjust(
        companyId: $this->company->id,
        productId: $product->id,
        locationId: $this->stockLocation->id,
        deltaQty: 10.0
    );

    // Assert
    expect($product->refresh()->quantity_on_hand)->toBe(10.0);

    // Test multiple adjustments across different locations
    $stockQuantService->adjust(
        companyId: $this->company->id,
        productId: $product->id,
        locationId: $this->vendorLocation->id,
        deltaQty: 5.0
    );

    // Total quantity should be 15
    expect($product->refresh()->quantity_on_hand)->toBe(15.0);

    // Verify quantity at specific location
    expect($product->getQuantityAtLocation($this->stockLocation->id))->toBe(10.0);
    expect($product->getQuantityAtLocation($this->vendorLocation->id))->toBe(5.0);
});

test('service products do not track inventory by default', function () {
    /** @var \Tests\TestCase $this */
    /** @var Product $product */
    $product = Product::factory()->for($this->company)->create([
        'type' => ProductType::Service,
    ]);

    // Service products should have 0 qty on hand and no stock quants
    expect($product->quantity_on_hand)->toBe(0.0);
    expect($product->stockQuants()->count())->toBe(0);
});
