<?php

namespace Modules\Inventory\Tests\Feature\Inventory;

use App\Actions\Purchases\CreateVendorBillAction;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\Enums\Inventory\ReorderingRoute;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Inventory\ValuationMethod;
use App\Enums\Products\ProductType;
use App\Models\Lot;
use App\Models\ReorderingRule;
use App\Models\StockMove;
use App\Models\StockQuant;
use App\Services\Inventory\InventoryReportingService;
use App\Services\Inventory\InventoryValuationService;
use App\Services\VendorBillService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    // Create COGS account for inventory tests
    $this->cogsAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create([
        'type' => 'cost_of_revenue',
        'name' => 'Cost of Goods Sold',
    ]);

    // Alias stockLocation as warehouseLocation for consistency with other tests
    $this->warehouseLocation = $this->stockLocation;

    $this->reportingService = app(InventoryReportingService::class);
});

it('calculates correct valuation at specific date using AVCO method', function () {
    // Arrange
    $product = \Modules\Product\Models\Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'average_cost' => Money::of(0, $this->company->currency->code),
        'default_cogs_account_id' => $this->cogsAccount->id,
        'average_cost' => Money::of(0, $this->company->currency->code),
    ]);

    // Create stock movements at different dates
    $date1 = Carbon::create(2025, 1, 15);
    $date2 = Carbon::create(2025, 2, 15);
    $date3 = Carbon::create(2025, 3, 15);

    // First receipt: 100 units at $10 each
    Carbon::setTestNow($date1);
    createStockReceipt($this, $product, 100, Money::of(10, 'IQD'), $date1);

    // Second receipt: 50 units at $12 each
    Carbon::setTestNow($date2);
    createStockReceipt($this, $product, 50, Money::of(12, 'IQD'), $date2);

    // Delivery: 30 units
    Carbon::setTestNow($date3);
    createStockDelivery($this, $product, 30, $date3);

    // Act - Get valuation at different dates
    $valuationAtDate1 = $this->reportingService->valuationAt($date1->endOfDay());
    $valuationAtDate2 = $this->reportingService->valuationAt($date2->endOfDay());
    $valuationAtDate3 = $this->reportingService->valuationAt($date3->endOfDay());

    // Assert
    expect($valuationAtDate1['total_value']->getAmount()->toFloat())->toEqualWithDelta(1000.0, 0.1); // 100 * $10
    expect($valuationAtDate1['total_quantity'])->toBe(100.0);

    expect($valuationAtDate2['total_value']->getAmount()->toFloat())->toEqualWithDelta(1600.0, 0.1); // (100*$10) + (50*$12)
    expect($valuationAtDate2['total_quantity'])->toBe(150.0);

    // After delivery: 120 units at average cost of $10.67
    expect($valuationAtDate3['total_quantity'])->toBe(120.0);
    expect($valuationAtDate3['total_value']->getAmount()->toFloat())->toEqualWithDelta(1280.0, 1.0);
});

it('calculates correct valuation using FIFO method with cost layers', function () {
    // Arrange
    $product = \Modules\Product\Models\Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::FIFO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'average_cost' => Money::of(0, $this->company->currency->code),
    ]);

    $date1 = Carbon::create(2025, 1, 15);
    $date2 = Carbon::create(2025, 2, 15);
    $date3 = Carbon::create(2025, 3, 15);

    // Create cost layers
    Carbon::setTestNow($date1);
    createStockReceipt($this, $product, 100, Money::of(10, 'IQD'), $date1);

    Carbon::setTestNow($date2);
    createStockReceipt($this, $product, 50, Money::of(12, 'IQD'), $date2);

    // Consume 30 units (should consume from first layer)
    Carbon::setTestNow($date3);
    createStockDelivery($this, $product, 30, $date3);

    // Act
    $valuation = $this->reportingService->valuationAt($date3->endOfDay());

    // Assert - Should have 70 units from first layer + 50 from second
    expect($valuation['total_quantity'])->toBe(120.0);
    expect($valuation['total_value']->getAmount()->toFloat())->toBe(1300.0); // (70*$10) + (50*$12)

    // Check cost layer breakdown
    expect($valuation['by_product'])->toHaveKey($product->id);
    $productValuation = $valuation['by_product'][$product->id];
    expect($productValuation['cost_layers'])->toHaveCount(2);
});

it('reconciles valuation with GL account balances', function () {
    // Arrange
    $product = \Modules\Product\Models\Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'average_cost' => Money::of(0, $this->company->currency->code),
    ]);

    $date = Carbon::create(2025, 1, 15);
    Carbon::setTestNow($date);

    // Create stock movements
    createStockReceipt($this, $product, 100, Money::of(10, 'IQD'), $date);

    // Act
    $valuation = $this->reportingService->valuationAt($date->endOfDay());
    $glReconciliation = $this->reportingService->reconcileWithGL($date->endOfDay());

    // Assert
    expect($valuation['total_value'])->toEqual($glReconciliation['inventory_account_balance']);
    expect($glReconciliation['reconciliation_difference']->isZero())->toBeTrue();
});

it('correctly ages inventory by receipt date', function () {
    // Arrange
    $product = \Modules\Product\Models\Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::FIFO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'average_cost' => Money::of(0, $this->company->currency->code),
    ]);

    // Use a fixed current date for consistent testing
    $currentDate = Carbon::parse('2025-09-18 12:00:00');
    $oldDate = $currentDate->copy()->subDays(45);
    $recentDate = $currentDate->copy()->subDays(15);

    // Create old stock
    Carbon::setTestNow($oldDate);
    createStockReceipt($this, $product, 50, Money::of(10, 'IQD'), $oldDate);

    // Create recent stock
    Carbon::setTestNow($recentDate);
    createStockReceipt($this, $product, 30, Money::of(12, 'IQD'), $recentDate);

    // Set test now to current date for ageing calculation
    Carbon::setTestNow($currentDate);



    // Act
    $ageingReport = $this->reportingService->ageing([
        'buckets' => [
            ['min' => 0, 'max' => 30, 'label' => '0-30 days'],
            ['min' => 31, 'max' => 60, 'label' => '31-60 days'],
            ['min' => 61, 'max' => 90, 'label' => '61-90 days'],
        ]
    ]);

    // Assert
    expect($ageingReport['buckets']['0-30 days']['quantity'])->toBe(30.0);
    expect($ageingReport['buckets']['0-30 days']['value']->getAmount()->toFloat())->toBe(360.0);

    expect($ageingReport['buckets']['31-60 days']['quantity'])->toBe(50.0);
    expect($ageingReport['buckets']['31-60 days']['value']->getAmount()->toFloat())->toBe(500.0);

    expect($ageingReport['total_quantity'])->toBe(80.0);
    expect($ageingReport['total_value']->getAmount()->toFloat())->toBe(860.0);
});

it('handles lot expiration in ageing report', function () {
    // Arrange
    $product = \Modules\Product\Models\Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::FIFO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'average_cost' => Money::of(0, $this->company->currency->code),
    ]);

    // Use a fixed current date for consistent testing
    $currentDate = Carbon::parse('2025-09-18 12:00:00');
    $receiptDate = $currentDate->copy()->subDays(20);

    $lot = Lot::factory()->for($this->company)->for($product)->create([
        'lot_code' => 'LOT-001',
        'expiration_date' => $currentDate->copy()->addDays(10), // Expires in 10 days from current date
    ]);

    Carbon::setTestNow($receiptDate);

    // Create stock with lot
    createStockReceiptWithLot($this, $product, $lot, 100, Money::of(10, 'IQD'), $receiptDate);

    Carbon::setTestNow($currentDate);

    // Act
    $ageingReport = $this->reportingService->ageing([
        'include_expiration' => true,
        'expiration_warning_days' => 30,
    ]);

    // Assert
    expect($ageingReport['expiring_soon'])->toHaveCount(1);
    expect($ageingReport['expiring_soon'][0]['lot_code'])->toBe('LOT-001');
    expect($ageingReport['expiring_soon'][0]['days_until_expiration'])->toBe(9);
});

it('calculates inventory turnover correctly', function () {
    // Arrange
    $product = \Modules\Product\Models\Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'average_cost' => Money::of(0, $this->company->currency->code),
        'default_cogs_account_id' => $this->cogsAccount->id,
    ]);

    $startDate = Carbon::create(2025, 1, 1);
    $endDate = Carbon::create(2025, 12, 31);

    // Create initial inventory
    Carbon::setTestNow($startDate);
    createStockReceipt($this, $product, 1000, Money::of(10, 'IQD'), $startDate);

    // Create sales throughout the year
    for ($month = 1; $month <= 12; $month++) {
        $saleDate = Carbon::create(2025, $month, 15);
        Carbon::setTestNow($saleDate);
        createStockDelivery($this, $product, 50, $saleDate); // 600 units total
    }

    // Add more inventory mid-year
    Carbon::setTestNow(Carbon::create(2025, 6, 15));
    createStockReceipt($this, $product, 500, Money::of(11, 'IQD'), Carbon::create(2025, 6, 15));

    Carbon::setTestNow($endDate);

    // Act
    $turnoverReport = $this->reportingService->turnover([
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);

    // Assert
    expect($turnoverReport['total_cogs']->getAmount()->toFloat())->toBeGreaterThan(0);
    expect($turnoverReport['average_inventory_value']->getAmount()->toFloat())->toBeGreaterThan(0);
    expect($turnoverReport['inventory_turnover_ratio'])->toBeGreaterThan(0);
    expect($turnoverReport['days_sales_inventory'])->toBeGreaterThan(0);
});

// Helper functions
function createStockReceipt($testCase, \Modules\Product\Models\Product $product, float $quantity, Money $costPerUnit, Carbon $date): void
{
    $valuationService = app(InventoryValuationService::class);
    $quantService = app(\App\Services\Inventory\StockQuantService::class);

    // Ensure product has proper relationships loaded
    $product = $product->fresh(['company.currency']);

    $stockMove = \App\Models\StockMove::factory()->create([
        'company_id' => $product->company_id,
        'product_id' => $product->id,  // This will be handled by the factory
        'quantity' => $quantity,       // This will be handled by the factory
        'from_location_id' => $testCase->vendorLocation->id,  // This will be handled by the factory
        'to_location_id' => $testCase->warehouseLocation->id, // This will be handled by the factory
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Done,
        'move_date' => $date,
        'reference' => 'TEST-RECEIPT-' . $date->format('Ymd'),
        'source_type' => 'Test',
        'source_id' => 1,
        'created_by_user_id' => $testCase->user->id,
    ]);

    $valuationService->processIncomingStock($product, $quantity, $costPerUnit, $date, $stockMove);
    $quantService->applyForIncoming($stockMove);
}

function createStockDelivery($testCase, \Modules\Product\Models\Product $product, float $quantity, Carbon $date): void
{
    $valuationService = app(InventoryValuationService::class);
    $quantService = app(\App\Services\Inventory\StockQuantService::class);

    // Ensure product has proper relationships loaded
    $product = $product->fresh(['company.currency']);

    $stockMove = StockMove::create([
        'company_id' => $product->company_id,
        'product_id' => $product->id,
        'quantity' => $quantity,
        'from_location_id' => $testCase->warehouseLocation->id,
        'to_location_id' => $testCase->customerLocation->id,
        'move_type' => StockMoveType::Outgoing,
        'status' => StockMoveStatus::Done,
        'move_date' => $date,
        'reference' => 'TEST-DELIVERY-' . $date->format('Ymd'),
        'source_type' => 'Test',
        'source_id' => 1,
        'created_by_user_id' => $testCase->user->id,
    ]);

    $valuationService->processOutgoingStock($product, $quantity, $date, $stockMove);
    $quantService->applyForOutgoing($stockMove);
}

function createStockReceiptWithLot($testCase, \Modules\Product\Models\Product $product, Lot $lot, float $quantity, Money $costPerUnit, Carbon $date): void
{
    $valuationService = app(InventoryValuationService::class);
    $quantService = app(\App\Services\Inventory\StockQuantService::class);

    // Ensure product has proper relationships loaded
    $product = $product->fresh(['company.currency']);

    $stockMove = StockMove::factory()->create([
        'company_id' => $product->company_id,
        'product_id' => $product->id,  // This will be handled by the factory
        'quantity' => $quantity,       // This will be handled by the factory
        'from_location_id' => $testCase->vendorLocation->id,  // This will be handled by the factory
        'to_location_id' => $testCase->warehouseLocation->id, // This will be handled by the factory
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Done,
        'move_date' => $date,
        'reference' => 'TEST-RECEIPT-LOT-' . $date->format('Ymd'),
        'source_type' => 'Test',
        'source_id' => 1,
        'created_by_user_id' => $testCase->user->id,
    ]);

    $valuationService->processIncomingStock($product, $quantity, $costPerUnit, $date, $stockMove);
    $quantService->applyForIncomingWithLot($stockMove, $lot->id);

    // Create stock move line for lot tracking
    // The factory should have created a product line, but let's ensure it exists
    $productLine = $stockMove->productLines()->first();
    if (!$productLine) {
        // Fallback: create product line manually
        $productLine = $stockMove->productLines()->create([
            'company_id' => $product->company_id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'from_location_id' => $testCase->vendorLocation->id,
            'to_location_id' => $testCase->warehouseLocation->id,
            'description' => 'Test product line',
            'source_type' => 'Test',
            'source_id' => 1,
        ]);
    }

    $stockMove->stockMoveLines()->create([
        'company_id' => $product->company_id,
        'stock_move_product_line_id' => $productLine->id,
        'lot_id' => $lot->id,
        'quantity' => $quantity,
    ]);
}

function createStockDeliveryWithLot($testCase, \Modules\Product\Models\Product $product, Lot $lot, float $quantity, Carbon $date): void
{
    $valuationService = app(InventoryValuationService::class);

    $stockMove = StockMove::factory()->create([
        'company_id' => $product->company_id,
        'product_id' => $product->id,  // This will be handled by the factory
        'quantity' => $quantity,       // This will be handled by the factory
        'from_location_id' => $testCase->warehouseLocation->id,  // This will be handled by the factory
        'to_location_id' => $testCase->customerLocation->id,     // This will be handled by the factory
        'move_type' => StockMoveType::Outgoing,
        'status' => StockMoveStatus::Done,
        'move_date' => $date,
        'reference' => 'TEST-DELIVERY-LOT-' . $date->format('Ymd'),
        'source_type' => 'Test',
        'source_id' => 1,
        'created_by_user_id' => $testCase->user->id,
    ]);

    $valuationService->processOutgoingStock($product, $quantity, $date, $stockMove);

    // Create stock move line for lot tracking
    // The factory should have created a product line
    $productLine = $stockMove->productLines()->first();

    $stockMove->stockMoveLines()->create([
        'company_id' => $product->company_id,
        'stock_move_product_line_id' => $productLine->id,
        'lot_id' => $lot->id,
        'quantity' => $quantity,
    ]);
}

it('traces complete lot journey from receipt to delivery', function () {
    // Arrange
    $product = \Modules\Product\Models\Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::FIFO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'average_cost' => Money::of(0, $this->company->currency->code),
    ]);

    $lot = Lot::factory()->for($this->company)->for($product)->create([
        'lot_code' => 'LOT-TRACE-001',
        'expiration_date' => Carbon::now()->addMonths(6),
    ]);

    $receiptDate = Carbon::now()->subDays(30);
    $deliveryDate = Carbon::now()->subDays(10);

    // Create receipt
    Carbon::setTestNow($receiptDate);
    createStockReceiptWithLot($this, $product, $lot, 100, Money::of(15, 'IQD'), $receiptDate);

    // Create partial delivery
    Carbon::setTestNow($deliveryDate);
    createStockDeliveryWithLot($this, $product, $lot, 30, $deliveryDate);

    Carbon::setTestNow(Carbon::now());

    // Act
    $traceReport = $this->reportingService->lotTrace($product, $lot);

    // Assert
    expect($traceReport['lot_code'])->toBe('LOT-TRACE-001');
    expect($traceReport['product_name'])->toBe($product->name);
    expect($traceReport['movements'])->toHaveCount(2);

    $receipt = $traceReport['movements'][0];
    expect($receipt['move_type'])->toBe(StockMoveType::Incoming);
    expect($receipt['quantity'])->toBe(100.0);
    expect($receipt['move_date']->format('Y-m-d'))->toBe($receiptDate->format('Y-m-d'));

    $delivery = $traceReport['movements'][1];
    expect($delivery['move_type'])->toBe(StockMoveType::Outgoing);
    expect($delivery['quantity'])->toBe(30.0);
    expect($delivery['move_date']->format('Y-m-d'))->toBe($deliveryDate->format('Y-m-d'));

    expect($traceReport['current_quantity'])->toBe(70.0);
    expect($traceReport['total_value']->getAmount()->toFloat())->toBe(1050.0); // 70 * $15
});

it('includes journal entry links in lot trace', function () {
    // Arrange
    $product = \Modules\Product\Models\Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::FIFO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'average_cost' => Money::of(0, $this->company->currency->code),
    ]);

    $lot = Lot::factory()->for($this->company)->for($product)->create([
        'lot_code' => 'LOT-JE-001',
    ]);

    $date = Carbon::now()->subDays(15);
    Carbon::setTestNow($date);

    createStockReceiptWithLot($this, $product, $lot, 50, Money::of(20, 'IQD'), $date);

    // Act
    $traceReport = $this->reportingService->lotTrace($product, $lot);

    // Assert
    expect($traceReport['movements'][0])->toHaveKey('journal_entry_id');
    expect($traceReport['movements'][0]['journal_entry_id'])->not->toBeNull();
    expect($traceReport['movements'][0])->toHaveKey('valuation_amount');
});

it('identifies products below minimum stock levels', function () {
    // Arrange
    $product = \Modules\Product\Models\Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'average_cost' => Money::of(0, $this->company->currency->code),
    ]);

    ReorderingRule::factory()->for($this->company)->create([
        'product_id' => $product->id,
        'location_id' => $this->warehouseLocation->id,
        'min_qty' => 100,
        'max_qty' => 500,
        'safety_stock' => 50,
        'multiple' => 1, // Explicitly set to 1 to ensure predictable calculation
        'route' => ReorderingRoute::MinMax,
        'active' => true,
    ]);

    // Create stock below minimum
    $date = Carbon::now()->subDays(5);
    Carbon::setTestNow($date);
    createStockReceipt($this, $product, 30, Money::of(10, 'IQD'), $date);

    Carbon::setTestNow(Carbon::now());

    // Act
    $reorderStatus = $this->reportingService->reorderStatus();

    // Assert
    expect($reorderStatus['below_minimum'])->toHaveCount(1);
    expect($reorderStatus['below_minimum'][0]['product_id'])->toBe($product->id);
    expect($reorderStatus['below_minimum'][0]['current_qty'])->toBe(30.0);
    expect($reorderStatus['below_minimum'][0]['min_qty'])->toBe(100.0);
    expect($reorderStatus['below_minimum'][0]['suggested_qty'])->toBe(470.0); // max - current
    expect($reorderStatus['below_minimum'][0]['priority'])->toBe('high'); // Below safety stock
});

it('calculates available to promise correctly with reservations', function () {
    // Arrange
    $product = \Modules\Product\Models\Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'average_cost' => Money::of(0, $this->company->currency->code),
    ]);

    ReorderingRule::factory()->for($this->company)->create([
        'product_id' => $product->id,
        'location_id' => $this->warehouseLocation->id,
        'min_qty' => 50,
        'max_qty' => 200,
        'safety_stock' => 25,
        'route' => ReorderingRoute::MinMax,
        'active' => true,
    ]);

    // Create stock
    $date = Carbon::now()->subDays(5);
    Carbon::setTestNow($date);
    createStockReceipt($this, $product, 100, Money::of(10, 'IQD'), $date);

    // Create reservations
    StockQuant::where('product_id', $product->id)
        ->where('location_id', $this->warehouseLocation->id)
        ->update(['reserved_quantity' => 30]);

    Carbon::setTestNow(Carbon::now());

    // Act
    $reorderStatus = $this->reportingService->reorderStatus();

    // Assert
    expect($reorderStatus['summary']['total_on_hand'])->toBe(100.0);
    expect($reorderStatus['summary']['total_reserved'])->toBe(30.0);
    expect($reorderStatus['summary']['total_available'])->toBe(70.0);

    // Should not trigger reorder since available (70) > min (50)
    expect($reorderStatus['below_minimum'])->toHaveCount(0);
});
