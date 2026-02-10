<?php

namespace Kezi\Inventory\Tests\Feature\Inventory;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Inventory\Enums\Inventory\InventoryAccountingMode;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Inventory\Exceptions\Inventory\InsufficientCostInformationException;
use Kezi\Inventory\Models\InventoryCostLayer;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockMoveProductLine;
use Kezi\Inventory\Models\StockMoveValuation;
use Kezi\Inventory\Models\StockQuant;
use Kezi\Inventory\Services\Inventory\InventoryValuationService;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Purchase\Models\VendorBillLine;
use Tests\TestCase;
use Tests\Traits\WithConfiguredCompany;

/**
 * Integration test for manual inventory recording mode
 *
 * This test covers the complete scenario where:
 * 1. Company uses manual inventory recording mode
 * 2. Vendor bills are posted but don't create stock moves automatically
 * 3. Stock moves are created manually later
 * 4. Cost determination uses posted vendor bills as source
 * 5. Stock moves create proper journal entries and update stock quants
 */
class ManualInventoryRecordingIntegrationTest extends TestCase
{
    use RefreshDatabase;
    use WithConfiguredCompany;

    // Properties set up by WithConfiguredCompany trait
    protected $company;

    protected $user;

    protected $vendor;

    protected $vendorLocation;

    protected $stockLocation;

    protected $inventoryAccount;

    protected $stockInputAccount;

    protected $cogsAccount;

    // Test-specific properties
    protected Product $product;

    protected VendorBill $vendorBill;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up company with inventory environment
        $this->setupWithConfiguredCompany();
        $this->setupInventoryTestEnvironment();

        // Update company to use manual inventory recording mode
        $this->company->update([
            'inventory_accounting_mode' => InventoryAccountingMode::MANUAL_INVENTORY_RECORDING,
        ]);

        // Create test product with FIFO valuation
        $this->product = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::Fifo,
            'quantity_on_hand' => 0,
            'average_cost' => Money::of(0, $this->company->currency->code),
        ]);

        // Create and post vendor bill
        $this->vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'status' => VendorBillStatus::Posted,
            'posted_at' => now(),
            'bill_reference' => 'VB-TEST-001',
        ]);

        VendorBillLine::factory()->create([
            'vendor_bill_id' => $this->vendorBill->id,
            'product_id' => $this->product->id,
            'quantity' => 10.0,
            'unit_price' => Money::of(1500, $this->company->currency->code),
        ]);
    }

    public function test_manual_inventory_recording_complete_scenario(): void
    {
        // Verify initial state: no stock moves or cost layers exist
        $this->assertDatabaseCount('stock_moves', 0);
        $this->assertDatabaseCount('inventory_cost_layers', 0);
        $this->assertDatabaseCount('stock_quants', 0);
        $this->assertEquals(0, $this->product->quantity_on_hand);

        // Create manual stock move
        $stockMove = StockMove::create([
            'company_id' => $this->company->id,
            'move_type' => StockMoveType::Incoming,
            'status' => StockMoveStatus::Draft,
            'move_date' => now(),
            'reference' => 'SM-MANUAL-001',
            'created_by_user_id' => $this->user->id,
        ]);

        // Create product line
        StockMoveProductLine::create([
            'stock_move_id' => $stockMove->id,
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'quantity' => 5.0,
            'from_location_id' => $this->vendorLocation->id,
            'to_location_id' => $this->stockLocation->id,
            'description' => 'Manual receipt',
        ]);

        // Verify cost determination works before processing
        $inventoryValuationService = app(InventoryValuationService::class);
        $costResult = $inventoryValuationService->calculateIncomingCostPerUnitEnhanced(
            $this->product,
            $stockMove,
            false
        );

        $this->assertTrue($costResult->cost->isPositive());
        $this->assertEquals('vendor_bill', $costResult->source->value);
        $this->assertStringContainsString('VendorBill:', $costResult->reference);

        // Process the stock move by changing status to Done
        $stockMove->update(['status' => StockMoveStatus::Done]);

        // Verify cost layer was created
        $this->assertDatabaseCount('inventory_cost_layers', 1);
        $costLayer = InventoryCostLayer::where('product_id', $this->product->id)->first();
        $this->assertNotNull($costLayer);
        $this->assertEquals(5.0, $costLayer->quantity);
        $this->assertEquals(StockMove::class, $costLayer->source_type);
        $this->assertEquals($stockMove->id, $costLayer->source_id);

        // Verify stock quant was created
        $this->assertDatabaseCount('stock_quants', 1);
        $stockQuant = StockQuant::where('product_id', $this->product->id)->first();
        $this->assertNotNull($stockQuant);
        $this->assertEquals(5, $stockQuant->quantity);
        $this->assertEquals($this->stockLocation->id, $stockQuant->location_id);

        // Verify journal entry was created
        $this->assertDatabaseCount('stock_move_valuations', 1);
        $valuation = StockMoveValuation::where('stock_move_id', $stockMove->id)->first();
        $this->assertNotNull($valuation);
        $this->assertTrue($valuation->cost_impact->isPositive());

        // Verify product quantity was updated
        $this->product->refresh();
        $this->assertEquals(5, $this->product->quantity_on_hand);
    }

    public function test_cost_determination_uses_latest_vendor_bill(): void
    {
        // Create second vendor bill with different price
        $secondVendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'status' => VendorBillStatus::Posted,
            'posted_at' => now()->addDay(),
            'bill_reference' => 'VB-TEST-002',
        ]);

        VendorBillLine::factory()->create([
            'vendor_bill_id' => $secondVendorBill->id,
            'product_id' => $this->product->id,
            'quantity' => 5.0,
            'unit_price' => Money::of(2000, $this->company->currency->code), // Higher price
        ]);

        // Create manual stock move
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
            'quantity' => 3.0,
            'from_location_id' => $this->vendorLocation->id,
            'to_location_id' => $this->stockLocation->id,
        ]);

        // Verify cost determination uses the latest vendor bill
        $inventoryValuationService = app(InventoryValuationService::class);
        $costResult = $inventoryValuationService->calculateIncomingCostPerUnitEnhanced(
            $this->product,
            $stockMove,
            false
        );

        $expectedCost = Money::of(2000, $this->company->currency->code);
        $this->assertTrue($costResult->cost->isEqualTo($expectedCost));
        $this->assertStringContainsString("VendorBill:{$secondVendorBill->id}", $costResult->reference);
    }

    public function test_cost_determination_fails_without_posted_vendor_bills(): void
    {
        // Create product without any vendor bills and zero average cost
        $productWithoutBills = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::Fifo,
            'average_cost' => Money::of(0, $this->company->currency->code),
        ]);

        $stockMove = StockMove::create([
            'company_id' => $this->company->id,
            'move_type' => StockMoveType::Incoming,
            'status' => StockMoveStatus::Draft,
            'move_date' => now(),
            'reference' => 'SM-NO-COST',
            'created_by_user_id' => $this->user->id,
        ]);

        StockMoveProductLine::create([
            'stock_move_id' => $stockMove->id,
            'company_id' => $this->company->id,
            'product_id' => $productWithoutBills->id,
            'quantity' => 1.0,
            'from_location_id' => $this->vendorLocation->id,
            'to_location_id' => $this->stockLocation->id,
        ]);

        // Verify cost determination fails
        $inventoryValuationService = app(InventoryValuationService::class);

        $this->expectException(InsufficientCostInformationException::class);
        $inventoryValuationService->calculateIncomingCostPerUnitEnhanced(
            $productWithoutBills,
            $stockMove,
            false
        );
    }
}
