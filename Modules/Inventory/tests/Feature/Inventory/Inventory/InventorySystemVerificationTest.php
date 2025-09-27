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
    $this->cogsAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create([
        'name' => 'Cost of Goods Sold',
        'type' => 'expense'
    ]);

    // Set up warehouse location alias
    $this->warehouseLocation = $this->stockLocation;
});

describe('Inventory System Verification with Sample Figures', function () {
    it('verifies basic inventory operations work correctly', function () {
        // Create a simple product
        $product = \Modules\Product\Models\Product::factory()->for($this->company)->create([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'type' => \Modules\Product\Enums\Products\ProductType::Storable,
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

        createStockReceiptForTest(test(), $product, 10, Money::of(50000, 'IQD'), $receiptDate);

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

/**
 * Helper function to create a stock receipt with proper valuation and quant updates
 */
function createStockReceiptForTest($testCase, \Modules\Product\Models\Product $product, float $quantity, Money $costPerUnit, Carbon $date): void
{
    $valuationService = app(\App\Services\Inventory\InventoryValuationService::class);
    $quantService = app(\App\Services\Inventory\StockQuantService::class);

    // Create a mock VendorBill for testing
    $vendorBill = \Modules\Purchase\Models\VendorBill::factory()->for($product->company)->create([
        'bill_date' => $date,
        'accounting_date' => $date,
        'total_amount' => $costPerUnit->multipliedBy($quantity),
    ]);

    // Create a stock move for the incoming stock using the factory for proper structure
    $stockMove = \App\Models\StockMove::factory()->create([
        'company_id' => $product->company_id,
        'product_id' => $product->id,  // This will be handled by the factory
        'quantity' => $quantity,       // This will be handled by the factory
        'from_location_id' => $product->company->vendorLocation->id,  // This will be handled by the factory
        'to_location_id' => $product->company->defaultStockLocation->id, // This will be handled by the factory
        'move_type' => \App\Enums\Inventory\StockMoveType::Incoming,
        'status' => \App\Enums\Inventory\StockMoveStatus::Done,
        'move_date' => $date,
        'reference' => 'TEST-RECEIPT-' . $date->format('Ymd'),
        'source_type' => \Modules\Purchase\Models\VendorBill::class,
        'source_id' => $vendorBill->id,
        'created_by_user_id' => $testCase->user->id,
    ]);

    // Process incoming stock through valuation service
    // This will handle both journal entries and stock quant updates
    $valuationService->processIncomingStock($product, $quantity, $costPerUnit, $date, $vendorBill);
}
