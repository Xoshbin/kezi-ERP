<?php

namespace Modules\Inventory\Tests\Feature\Actions\Inventory;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Actions\Inventory\ProcessIncomingStockAction;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Modules\Inventory\Models\InventoryCostLayer;
use Modules\Inventory\Models\StockLocation;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Models\StockMoveProductLine;
use Modules\Inventory\Models\StockQuant;
use Modules\Product\Models\Product;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Models\VendorBillLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();
    $this->action = app(ProcessIncomingStockAction::class);
});

it('processes incoming stock from vendor bill', function () {
    // Setup Product and Accounts
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => \Modules\Inventory\Enums\Inventory\ValuationMethod::FIFO,
        // Accounts are needed for Journal Entry creation
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
    ]);

    $destinationLocation = StockLocation::factory()->create(['company_id' => $this->company->id]);

    // Create Vendor Bill and Line
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'exchange_rate_at_creation' => 1.0,
    ]);

    $vendorBillLine = VendorBillLine::factory()->create([
        'vendor_bill_id' => $vendorBill->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'unit_price' => Money::of(100, $this->company->currency->code),
    ]);

    // Create Stock Move
    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Confirmed,
        'source_type' => VendorBill::class,
        'source_id' => $vendorBill->id,
    ]);

    $line = StockMoveProductLine::factory()->create([
        'stock_move_id' => $move->id,
        'product_id' => $product->id,
        'quantity' => 12.34,
        'to_location_id' => $destinationLocation->id,
        'company_id' => $this->company->id,
    ]);

    // Execute Action
    $this->action->execute($move);

    // Verify Stock Quant
    $quant = StockQuant::where('product_id', $product->id)
        ->where('location_id', $destinationLocation->id)
        ->first();

    expect($quant)->not->toBeNull()
        ->and($quant->quantity)->toEqual(12.34);

    // Verify Cost Layer (FIFO/LIFO is default)
    $layer = InventoryCostLayer::where('product_id', $product->id)
        ->where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->first();

    // Layer quantity comes from the Stock Move Line quantity which we passed 12.34
    // Wait, processIncomingStock uses $productLine->quantity

    expect($layer)->not->toBeNull()
        ->and($layer->quantity)->toEqual(12.34)
        ->and($layer->remaining_quantity)->toEqual(12.34)
        ->and($layer->cost_per_unit->isEqualTo(Money::of(100, $this->company->currency->code)))->toBeTrue();
});
