<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Inventory\Enums\Inventory\ValuationMethod;
use Jmeryar\Inventory\Models\StockQuant;
use Jmeryar\Inventory\Services\Inventory\StockQuantService;
use Jmeryar\Product\Enums\Products\ProductType;
use Jmeryar\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    $this->product = Product::factory()->for($this->company)->create([
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'average_cost' => Money::of(0, $this->company->currency->code),
    ]);

    $this->service = app(StockQuantService::class);
});

it('upserts and adjusts quants atomically', function () {
    // Initially no quant
    expect(StockQuant::where('company_id', $this->company->id)
        ->where('product_id', $this->product->id)
        ->where('location_id', $this->stockLocation->id)
        ->exists())->toBeFalse();

    // Increase by 10
    $quant = $this->service->adjust($this->company->id, $this->product->id, $this->stockLocation->id, 10);

    expect($quant->quantity)->toBeFloat()->toBe(10.0);
    expect($quant->reserved_quantity)->toBeFloat()->toBe(0.0);

    // Available matches
    expect($this->service->available($this->company->id, $this->product->id, $this->stockLocation->id))
        ->toBe(10.0);

    // Reserve 3
    $quant = $this->service->reserve($this->company->id, $this->product->id, $this->stockLocation->id, 3);
    expect($quant->reserved_quantity)->toBe(3.0);
    expect($this->service->available($this->company->id, $this->product->id, $this->stockLocation->id))
        ->toBe(7.0);

    // Unreserve 2
    $quant = $this->service->unreserve($this->company->id, $this->product->id, $this->stockLocation->id, 2);
    expect($quant->reserved_quantity)->toBe(1.0);
    expect($this->service->available($this->company->id, $this->product->id, $this->stockLocation->id))
        ->toBe(9.0);
});

it('prevents negative quantities and over-reservation', function () {
    // Start with 5
    $this->service->adjust($this->company->id, $this->product->id, $this->stockLocation->id, 5);

    // Cannot reserve more than available
    expect(fn () => $this->service->reserve($this->company->id, $this->product->id, $this->stockLocation->id, 6))
        ->toThrow(\RuntimeException::class);

    // Cannot adjust below zero
    expect(fn () => $this->service->adjust($this->company->id, $this->product->id, $this->stockLocation->id, -10))
        ->toThrow(\RuntimeException::class);
});
