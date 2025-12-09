<?php

namespace Modules\Inventory\Tests\Feature\Inventory;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Enums\Inventory\InventoryAccountingMode;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Modules\Inventory\Enums\Inventory\ValuationMethod;
use Modules\Inventory\Exceptions\Inventory\InsufficientCostInformationException;
use Modules\Inventory\Models\InventoryCostLayer;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Models\StockMoveProductLine;
use Modules\Inventory\Services\Inventory\InventoryValuationService;
use Modules\Product\Models\Product;
use Modules\Purchase\Actions\Purchases\CreateVendorBillLineAction;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Services\VendorBillService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    // Set company to manual inventory recording mode
    $this->company->update([
        'inventory_accounting_mode' => InventoryAccountingMode::MANUAL_INVENTORY_RECORDING,
    ]);

    // Create test product
    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::FIFO,
        'quantity_on_hand' => 0,
        'average_cost' => Money::of(0, $this->company->currency->code),
    ]);
});

it('creates cost layers when stock moves are processed in manual mode even without vendor bill', function () {
    // Verify no cost layers exist initially
    expect(InventoryCostLayer::where('product_id', $this->product->id)->count())->toBe(0);

    // Create manual stock move without any vendor bill
    $stockMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-MANUAL-001',
        'created_by_user_id' => $this->user->id,
    ]);

    StockMoveProductLine::create([
        'stock_move_id' => $stockMove->id,
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'quantity' => 5.0,
        'from_location_id' => $this->vendorLocation->id,
        'to_location_id' => $this->stockLocation->id,
        'description' => 'Manual receipt without vendor bill',
    ]);

    // This should fail because there's no cost information available
    $inventoryValuationService = app(InventoryValuationService::class);

    expect(fn () => $inventoryValuationService->calculateIncomingCostPerUnitEnhanced(
        $this->product,
        $stockMove,
        false
    ))->toThrow(InsufficientCostInformationException::class);
});

it('creates cost layers when stock moves are processed after vendor bill in manual mode', function () {
    // First, create and post a vendor bill
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now()->format('Y-m-d'),
        'accounting_date' => now()->format('Y-m-d'),
    ]);

    $lineDto = new CreateVendorBillLineDTO(
        product_id: $this->product->id,
        description: 'Test Product Line',
        quantity: 10,
        unit_price: Money::of(1500, $this->company->currency->code),
        expense_account_id: $this->product->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );

    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto);
    app(VendorBillService::class)->post($vendorBill, $this->user);

    // Verify no cost layers were created by the vendor bill in manual mode
    expect(InventoryCostLayer::where('product_id', $this->product->id)->count())->toBe(0);

    // Now create manual stock move
    $stockMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-MANUAL-002',
        'created_by_user_id' => $this->user->id,
    ]);

    StockMoveProductLine::create([
        'stock_move_id' => $stockMove->id,
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'quantity' => 5.0,
        'from_location_id' => $this->vendorLocation->id,
        'to_location_id' => $this->stockLocation->id,
        'description' => 'Manual receipt after vendor bill',
    ]);

    // Cost determination should work using the vendor bill
    $inventoryValuationService = app(InventoryValuationService::class);
    $costResult = $inventoryValuationService->calculateIncomingCostPerUnitEnhanced(
        $this->product,
        $stockMove,
        false
    );

    expect($costResult->cost)->toEqual(Money::of(1500, $this->company->currency->code));
    expect($costResult->source->value)->toBe('vendor_bill');

    // Process the stock move
    $stockMove->update(['status' => StockMoveStatus::Done]);

    // Verify cost layer was created
    $costLayers = InventoryCostLayer::where('product_id', $this->product->id)->get();
    expect($costLayers)->toHaveCount(1);

    $costLayer = $costLayers->first();
    expect($costLayer->quantity)->toBe(5.0);
    expect($costLayer->cost_per_unit)->toEqual(Money::of(1500, $this->company->currency->code));
    expect($costLayer->source_type)->toBe(StockMove::class);
    expect($costLayer->source_id)->toBe($stockMove->id);
});

it('handles multiple vendor bills and uses the latest one for cost determination', function () {
    // Create first vendor bill
    $vendorBill1 = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now()->subDays(2)->format('Y-m-d'),
        'accounting_date' => now()->subDays(2)->format('Y-m-d'),
    ]);

    $lineDto1 = new CreateVendorBillLineDTO(
        product_id: $this->product->id,
        description: 'First Purchase',
        quantity: 10,
        unit_price: Money::of(1000, $this->company->currency->code),
        expense_account_id: $this->product->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );

    app(CreateVendorBillLineAction::class)->execute($vendorBill1, $lineDto1);
    app(VendorBillService::class)->post($vendorBill1, $this->user);

    // Add a small delay to ensure different posted_at timestamps
    sleep(1);

    // Create second vendor bill with different price
    $vendorBill2 = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now()->format('Y-m-d'),
        'accounting_date' => now()->format('Y-m-d'),
    ]);

    $lineDto2 = new CreateVendorBillLineDTO(
        product_id: $this->product->id,
        description: 'Second Purchase',
        quantity: 5,
        unit_price: Money::of(2000, $this->company->currency->code), // Higher price
        expense_account_id: $this->product->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );

    app(CreateVendorBillLineAction::class)->execute($vendorBill2, $lineDto2);
    app(VendorBillService::class)->post($vendorBill2, $this->user);

    // Create manual stock move
    $stockMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-MANUAL-003',
        'created_by_user_id' => $this->user->id,
    ]);

    StockMoveProductLine::create([
        'stock_move_id' => $stockMove->id,
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'quantity' => 3.0,
        'from_location_id' => $this->vendorLocation->id,
        'to_location_id' => $this->stockLocation->id,
        'description' => 'Manual receipt with multiple vendor bills',
    ]);

    // Cost determination should use the latest vendor bill
    $inventoryValuationService = app(InventoryValuationService::class);
    $costResult = $inventoryValuationService->calculateIncomingCostPerUnitEnhanced(
        $this->product,
        $stockMove,
        false
    );

    expect($costResult->cost)->toEqual(Money::of(2000, $this->company->currency->code));
    expect($costResult->reference)->toContain("VendorBill:{$vendorBill2->id}");
});
