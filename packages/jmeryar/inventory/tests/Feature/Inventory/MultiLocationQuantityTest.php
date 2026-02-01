<?php

namespace Jmeryar\Inventory\Tests\Feature\Inventory;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Inventory\Enums\Inventory\StockLocationType;
use Jmeryar\Inventory\Enums\Inventory\ValuationMethod;
use Jmeryar\Inventory\Models\StockLocation;
use Jmeryar\Inventory\Models\StockQuant;
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
        'average_cost' => Money::of(100, $this->company->currency->code),
    ]);

    // Create two warehouse locations
    $this->warehouseA = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Warehouse A',
        'type' => StockLocationType::Internal,
    ]);

    $this->warehouseB = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Warehouse B',
        'type' => StockLocationType::Internal,
    ]);
});

describe('Product.quantity_on_hand aggregates from StockQuant', function () {
    it('returns zero when no stock quants exist', function () {
        expect($this->product->quantity_on_hand)->toBe(0.0);
        expect($this->product->available_quantity)->toBe(0.0);
    });

    it('returns correct total across multiple locations', function () {
        // Add 50 units to Warehouse A
        StockQuant::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'location_id' => $this->warehouseA->id,
            'quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        // Add 30 units to Warehouse B
        StockQuant::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'location_id' => $this->warehouseB->id,
            'quantity' => 30,
            'reserved_quantity' => 0,
        ]);

        // Total across all locations should be 80
        expect($this->product->quantity_on_hand)->toBe(80.0);
        expect($this->product->available_quantity)->toBe(80.0);
    });

    it('calculates available quantity correctly with reservations', function () {
        StockQuant::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'location_id' => $this->warehouseA->id,
            'quantity' => 100,
            'reserved_quantity' => 25,
        ]);

        StockQuant::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'location_id' => $this->warehouseB->id,
            'quantity' => 50,
            'reserved_quantity' => 10,
        ]);

        // Total: 150, Reserved: 35, Available: 115
        expect($this->product->quantity_on_hand)->toBe(150.0);
        expect($this->product->available_quantity)->toBe(115.0);
    });
});

describe('Product location-specific quantity methods', function () {
    beforeEach(function () {
        StockQuant::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'location_id' => $this->warehouseA->id,
            'quantity' => 100,
            'reserved_quantity' => 20,
        ]);

        StockQuant::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'location_id' => $this->warehouseB->id,
            'quantity' => 50,
            'reserved_quantity' => 5,
        ]);
    });

    it('returns correct quantity at specific location', function () {
        expect($this->product->getQuantityAtLocation($this->warehouseA->id))->toBe(100.0);
        expect($this->product->getQuantityAtLocation($this->warehouseB->id))->toBe(50.0);
    });

    it('returns correct available quantity at specific location', function () {
        // Warehouse A: 100 - 20 = 80 available
        expect($this->product->getAvailableQuantityAtLocation($this->warehouseA->id))->toBe(80.0);

        // Warehouse B: 50 - 5 = 45 available
        expect($this->product->getAvailableQuantityAtLocation($this->warehouseB->id))->toBe(45.0);
    });

    it('returns zero for location with no stock', function () {
        $emptyWarehouse = StockLocation::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Empty Warehouse',
            'type' => StockLocationType::Internal,
        ]);

        expect($this->product->getQuantityAtLocation($emptyWarehouse->id))->toBe(0.0);
        expect($this->product->getAvailableQuantityAtLocation($emptyWarehouse->id))->toBe(0.0);
    });
});
