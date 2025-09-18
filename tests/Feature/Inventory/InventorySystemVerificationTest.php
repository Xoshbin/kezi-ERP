<?php

namespace Tests\Feature\Inventory;

use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Inventory\ValuationMethod;
use App\Enums\Products\ProductType;
use App\Models\InventoryCostLayer;
use App\Models\Lot;
use App\Models\Product;
use App\Models\StockMove;
use App\Models\StockQuant;
use App\Services\Inventory\InventoryReportingService;
use App\Services\Inventory\InventoryValuationService;
use App\Services\Inventory\StockQuantService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    $this->reportingService = app(InventoryReportingService::class);
    $this->valuationService = app(InventoryValuationService::class);
    $this->quantService = app(StockQuantService::class);

    // Create COGS account for testing
    $this->cogsAccount = \App\Models\Account::factory()->for($this->company)->create([
        'name' => 'Cost of Goods Sold',
        'type' => 'expense'
    ]);

    // Set up warehouse location alias
    $this->warehouseLocation = $this->stockLocation;
});

describe('Inventory System Verification with Sample Figures', function () {
    it('verifies basic inventory operations work correctly', function () {
        // Create a simple product
        $product = Product::factory()->for($this->company)->create([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'type' => ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::AVCO,
            'unit_price' => Money::of(100000, 'IQD'), // 100 IQD
            'default_inventory_account_id' => $this->inventoryAccount->id,
            'default_stock_input_account_id' => $this->stockInputAccount->id,
            'default_cogs_account_id' => $this->cogsAccount->id,
            'average_cost' => Money::of(0, 'IQD'),
        ]);

        // Test basic stock receipt
        $receiptDate = Carbon::now();
        Carbon::setTestNow($receiptDate);

        createStockReceipt($this, $product, 10, Money::of(50000, 'IQD'), $receiptDate);

        // Verify stock was received
        $stockQuantity = StockQuant::where('product_id', $product->id)->sum('quantity');
        expect((float) $stockQuantity)->toBe(10.0);

        // Test inventory valuation
        $valuation = $this->reportingService->valuationAt($receiptDate);
        expect($valuation)->toHaveKey('total_value')
            ->and($valuation['total_value'])->toBeInstanceOf(Money::class)
            ->and($valuation['total_value']->isPositive())->toBeTrue();

        // Test basic reporting functions
        $agingReport = $this->reportingService->ageing([30, 60, 90]);
        expect($agingReport)->toBeArray();

        $turnoverReport = $this->reportingService->turnover([
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now(),
        ]);
        expect($turnoverReport)->toBeArray();

        $reorderStatus = $this->reportingService->reorderStatus();
        expect($reorderStatus)->toBeArray();

        Carbon::setTestNow(); // Reset time
    });
});
