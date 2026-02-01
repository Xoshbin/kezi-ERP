<?php

namespace Jmeryar\Inventory\Tests\Feature\Actions\LandedCost;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Inventory\Actions\LandedCost\AllocateLandedCostsAction;
use Jmeryar\Inventory\Enums\Inventory\LandedCostAllocationMethod;
use Jmeryar\Inventory\Models\LandedCost;
use Jmeryar\Inventory\Models\StockMove;
use Jmeryar\Inventory\Models\StockMoveProductLine;
use Jmeryar\Inventory\Models\StockMoveValuation;
use Jmeryar\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();
    $this->action = app(AllocateLandedCostsAction::class);
});

it('allocates landed costs by quantity correctly', function () {
    $product1 = Product::factory()->create(['company_id' => $this->company->id]);
    $product2 = Product::factory()->create(['company_id' => $this->company->id]);

    $landedCost = LandedCost::create([
        'company_id' => $this->company->id,
        'amount_total' => Money::of(100, $this->company->currency->code),
        'allocation_method' => LandedCostAllocationMethod::ByQuantity,
        'status' => \Jmeryar\Inventory\Enums\Inventory\LandedCostStatus::Draft,
        'date' => now(),
    ]);

    $move1 = StockMove::factory()->create(['company_id' => $this->company->id]);
    StockMoveProductLine::factory()->create([
        'stock_move_id' => $move1->id,
        'product_id' => $product1->id,
        'quantity' => 10,
    ]);

    $move2 = StockMove::factory()->create(['company_id' => $this->company->id]);
    StockMoveProductLine::factory()->create([
        'stock_move_id' => $move2->id,
        'product_id' => $product2->id,
        'quantity' => 30,
    ]);

    $this->action->execute($landedCost, collect([$move1, $move2]));

    expect($landedCost->lines)->toHaveCount(2);

    $line1 = $landedCost->lines()->where('stock_move_id', $move1->id)->first();
    $line2 = $landedCost->lines()->where('stock_move_id', $move2->id)->first();

    // Total Qty = 40. Move1 (10/40 = 25%) -> $25. Move2 (30/40 = 75%) -> $75.
    expect($line1->additional_cost->isEqualTo(Money::of(25, $this->company->currency->code)))->toBeTrue();
    expect($line2->additional_cost->isEqualTo(Money::of(75, $this->company->currency->code)))->toBeTrue();
});

it('allocates landed costs by cost correctly', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);

    $landedCost = LandedCost::create([
        'company_id' => $this->company->id,
        'amount_total' => Money::of(100, $this->company->currency->code),
        'allocation_method' => LandedCostAllocationMethod::ByCost,
        'status' => \Jmeryar\Inventory\Enums\Inventory\LandedCostStatus::Draft,
        'date' => now(),
    ]);

    $move1 = StockMove::factory()->create(['company_id' => $this->company->id]);
    StockMoveValuation::factory()->create([
        'stock_move_id' => $move1->id,
        'product_id' => $product->id,
        'cost_impact' => Money::of(200, $this->company->currency->code),
    ]);

    $move2 = StockMove::factory()->create(['company_id' => $this->company->id]);
    StockMoveValuation::factory()->create([
        'stock_move_id' => $move2->id,
        'product_id' => $product->id,
        'cost_impact' => Money::of(800, $this->company->currency->code),
    ]);

    $this->action->execute($landedCost, collect([$move1, $move2]));

    $line1 = $landedCost->lines()->where('stock_move_id', $move1->id)->first();
    $line2 = $landedCost->lines()->where('stock_move_id', $move2->id)->first();

    // Total Cost = 1000. Move1 (200/1000 = 20%) -> $20. Move2 (800/1000 = 80%) -> $80.
    expect($line1->additional_cost->isEqualTo(Money::of(20, $this->company->currency->code)))->toBeTrue();
    expect($line2->additional_cost->isEqualTo(Money::of(80, $this->company->currency->code)))->toBeTrue();
});

it('allocates landed costs by weight or volume correctly', function () {
    $product1 = Product::factory()->create(['company_id' => $this->company->id, 'weight' => 2, 'volume' => 5]);
    $product2 = Product::factory()->create(['company_id' => $this->company->id, 'weight' => 8, 'volume' => 15]);

    // Test Weight
    $landedCostWeight = LandedCost::create([
        'company_id' => $this->company->id,
        'amount_total' => Money::of(100, $this->company->currency->code),
        'allocation_method' => LandedCostAllocationMethod::ByWeight,
        'status' => \Jmeryar\Inventory\Enums\Inventory\LandedCostStatus::Draft,
        'date' => now(),
    ]);

    $move1 = StockMove::factory()->create(['company_id' => $this->company->id]);
    StockMoveProductLine::factory()->create(['stock_move_id' => $move1->id, 'product_id' => $product1->id, 'quantity' => 1]);

    $move2 = StockMove::factory()->create(['company_id' => $this->company->id]);
    StockMoveProductLine::factory()->create(['stock_move_id' => $move2->id, 'product_id' => $product2->id, 'quantity' => 1]);

    $this->action->execute($landedCostWeight, collect([$move1, $move2]));

    // Total Weight = 2+8=10. Move1 (20%) -> $20. Move2 (80%) -> $80.
    expect($landedCostWeight->lines()->where('stock_move_id', $move1->id)->first()->additional_cost->isEqualTo(Money::of(20, $this->company->currency->code)))->toBeTrue();
    expect($landedCostWeight->lines()->where('stock_move_id', $move2->id)->first()->additional_cost->isEqualTo(Money::of(80, $this->company->currency->code)))->toBeTrue();

    // Test Volume
    $landedCostVolume = LandedCost::create([
        'company_id' => $this->company->id,
        'amount_total' => Money::of(100, $this->company->currency->code),
        'allocation_method' => LandedCostAllocationMethod::ByVolume,
        'status' => \Jmeryar\Inventory\Enums\Inventory\LandedCostStatus::Draft,
        'date' => now(),
    ]);

    $this->action->execute($landedCostVolume, collect([$move1, $move2]));

    // Total Volume = 5+15=20. Move1 (5/20 = 25%) -> $25. Move2 (15/20 = 75%) -> $75.
    expect($landedCostVolume->lines()->where('stock_move_id', $move1->id)->first()->additional_cost->isEqualTo(Money::of(25, $this->company->currency->code)))->toBeTrue();
    expect($landedCostVolume->lines()->where('stock_move_id', $move2->id)->first()->additional_cost->isEqualTo(Money::of(75, $this->company->currency->code)))->toBeTrue();
});

it('does nothing when stock moves collection is empty', function () {
    $landedCost = LandedCost::create([
        'company_id' => $this->company->id,
        'amount_total' => Money::of(100, $this->company->currency->code),
        'allocation_method' => LandedCostAllocationMethod::ByQuantity,
        'status' => \Jmeryar\Inventory\Enums\Inventory\LandedCostStatus::Draft,
        'date' => now(),
    ]);

    $this->action->execute($landedCost, collect([]));

    expect($landedCost->lines)->toBeEmpty();
});
