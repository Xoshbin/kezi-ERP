<?php

namespace Kezi\Inventory\Tests\Feature\Inventory;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockQuant;
use Kezi\Inventory\Services\Inventory\InventoryMovementValidationService;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    $this->product = Product::factory()->for($this->company)->create([
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::Avco,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'average_cost' => Money::of(100, $this->company->currency->code),
    ]);

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

    $this->validationService = app(InventoryMovementValidationService::class);
});

describe('Location-aware stock validation', function () {
    beforeEach(function () {
        // Warehouse A has 100 units (20 reserved = 80 available)
        StockQuant::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'location_id' => $this->warehouseA->id,
            'quantity' => 100,
            'reserved_quantity' => 20,
        ]);

        // Warehouse B has 30 units (all available)
        StockQuant::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'location_id' => $this->warehouseB->id,
            'quantity' => 30,
            'reserved_quantity' => 0,
        ]);
    });

    it('validates successfully when global quantity is sufficient', function () {
        // Total available: 80 + 30 = 110, requesting 50 without location = OK
        $result = $this->validationService->validateMovement(
            $this->product,
            StockMoveType::Outgoing,
            50
        );

        expect($result->isValid())->toBeTrue();
    });

    it('validates successfully when location has sufficient quantity', function () {
        // Warehouse A has 80 available, requesting 50 = OK
        $result = $this->validationService->validateMovement(
            $this->product,
            StockMoveType::Outgoing,
            50,
            $this->warehouseA->id
        );

        expect($result->isValid())->toBeTrue();
    });

    it('fails when location has insufficient quantity but global is sufficient', function () {
        // Warehouse B has 30 available, requesting 50 = FAIL
        // But globally we have 110 available
        $result = $this->validationService->validateMovement(
            $this->product,
            StockMoveType::Outgoing,
            50,
            $this->warehouseB->id
        );

        expect($result->isValid())->toBeFalse();
        expect($result->getErrors())->toContain(
            "Insufficient stock at location {$this->warehouseB->id}: Available 30, Requested 50"
        );
    });

    it('fails when requesting more than available (considering reservations)', function () {
        // Warehouse A has 100 total, 20 reserved = 80 available
        // Requesting 90 should fail
        $result = $this->validationService->validateMovement(
            $this->product,
            StockMoveType::Outgoing,
            90,
            $this->warehouseA->id
        );

        expect($result->isValid())->toBeFalse();
        expect($result->getErrors()[0])->toContain('Insufficient stock');
    });

    it('fails when location has no stock', function () {
        $emptyWarehouse = StockLocation::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Empty Warehouse',
            'type' => StockLocationType::Internal,
        ]);

        $result = $this->validationService->validateMovement(
            $this->product,
            StockMoveType::Outgoing,
            10,
            $emptyWarehouse->id
        );

        expect($result->isValid())->toBeFalse();
        expect($result->getErrors()[0])->toContain('Insufficient stock');
    });

    it('allows incoming movements regardless of current stock', function () {
        $result = $this->validationService->validateMovement(
            $this->product,
            StockMoveType::Incoming,
            1000,
            $this->warehouseA->id
        );

        expect($result->isValid())->toBeTrue();
    });
});
