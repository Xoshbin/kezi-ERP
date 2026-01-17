<?php

namespace Modules\Inventory\Tests\Feature\Actions\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Actions\Inventory\ProcessOutgoingStockAction;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Modules\Inventory\Enums\Inventory\ValuationMethod;
use Modules\Inventory\Models\InventoryCostLayer;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Models\StockMoveProductLine;
use Modules\Inventory\Models\StockQuant;
use Modules\Inventory\Models\StockReservation;
use Modules\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();
    $this->action = app(ProcessOutgoingStockAction::class);
});

it('processes outgoing stock and calculates COGS (AVCO)', function () {
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'average_cost' => \Brick\Money\Money::of(100, $this->company->currency->code),
    ]);

    // Setup quant and reservation
    $location = \Modules\Inventory\Models\StockLocation::factory()->create(['company_id' => $this->company->id]);
    StockQuant::create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'location_id' => $location->id,
        'quantity' => 20,
        'reserved_quantity' => 10,
    ]);

    $invoice = \Modules\Sales\Models\Invoice::factory()->create(['company_id' => $this->company->id]);

    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Outgoing,
        'source_type' => \Modules\Sales\Models\Invoice::class,
        'source_id' => $invoice->id,
        'status' => StockMoveStatus::Confirmed,
    ]);

    StockMoveProductLine::factory()->create([
        'stock_move_id' => $move->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'company_id' => $this->company->id,
        'from_location_id' => $location->id,
    ]);

    StockReservation::create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'stock_move_id' => $move->id,
        'location_id' => $location->id,
        'quantity' => 10,
    ]);

    $this->action->execute($move);

    // Verify Quant
    $quant = StockQuant::where('product_id', $product->id)->first();
    expect($quant->quantity)->toEqual(10)
        ->and($quant->reserved_quantity)->toEqual(0);

    // Verify Valuation
    $valuation = \Modules\Inventory\Models\StockMoveValuation::where('stock_move_id', $move->id)->first();
    expect($valuation)->not->toBeNull()
        ->and($valuation->cost_impact->isEqualTo(\Brick\Money\Money::of(1000, $this->company->currency->code)))->toBeTrue();
});

it('processes outgoing stock and calculates COGS (FIFO)', function () {
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'inventory_valuation_method' => ValuationMethod::FIFO,
    ]);

    InventoryCostLayer::create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'remaining_quantity' => 10,
        'cost_per_unit' => \Brick\Money\Money::of(100, $this->company->currency->code),
        'purchase_date' => now()->subDays(2),
        'source_type' => 'StockMove',
        'source_id' => 1,
    ]);

    InventoryCostLayer::create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'remaining_quantity' => 10,
        'cost_per_unit' => \Brick\Money\Money::of(150, $this->company->currency->code),
        'purchase_date' => now()->subDays(1),
        'source_type' => 'StockMove',
        'source_id' => 2,
    ]);

    // Setup quant and reservation
    $location = \Modules\Inventory\Models\StockLocation::factory()->create(['company_id' => $this->company->id]);
    StockQuant::create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'location_id' => $location->id,
        'quantity' => 20,
        'reserved_quantity' => 15,
    ]);

    $invoice = \Modules\Sales\Models\Invoice::factory()->create(['company_id' => $this->company->id]);

    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Outgoing,
        'source_type' => \Modules\Sales\Models\Invoice::class,
        'source_id' => $invoice->id,
    ]);

    StockMoveProductLine::factory()->create([
        'stock_move_id' => $move->id,
        'product_id' => $product->id,
        'quantity' => 15,
        'company_id' => $this->company->id,
        'from_location_id' => $location->id,
    ]);

    StockReservation::create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'stock_move_id' => $move->id,
        'location_id' => $location->id,
        'quantity' => 15,
    ]);

    $this->action->execute($move);

    // FIFO: 10 * 100 + 5 * 150 = 1000 + 750 = 1750
    $valuation = \Modules\Inventory\Models\StockMoveValuation::where('stock_move_id', $move->id)->first();
    expect($valuation->cost_impact->isEqualTo(\Brick\Money\Money::of(1750, $this->company->currency->code)))->toBeTrue();

    // Check layers
    $layer1 = InventoryCostLayer::orderBy('purchase_date', 'asc')->first();
    $layer2 = InventoryCostLayer::orderBy('purchase_date', 'asc')->skip(1)->first();
    expect($layer1->remaining_quantity)->toEqual(0)
        ->and($layer2->remaining_quantity)->toEqual(5);
});

it('throws exception if move has no source document', function () {
    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'source_type' => null,
        'source_id' => null,
    ]);

    $this->action->execute($move);
})->throws(\Exception::class, 'Stock move must have a source document');
