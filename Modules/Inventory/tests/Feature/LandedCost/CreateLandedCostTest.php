<?php

use Modules\Inventory\Actions\LandedCost\AllocateLandedCostsAction;
use Modules\Inventory\Actions\LandedCost\CreateLandedCostAction;
use Modules\Inventory\Actions\LandedCost\PostLandedCostAction;
use Modules\Inventory\DataTransferObjects\LandedCost\LandedCostData;
use Modules\Inventory\Enums\Inventory\LandedCostAllocationMethod;
use Modules\Inventory\Models\LandedCost;
use Modules\Inventory\Models\StockMoveValuation;

it('can create and post a landed cost', function () {
    // 1. Setup Data
    $company = \App\Models\Company::factory()->create();
    $currency = \Modules\Foundation\Models\Currency::factory()->create(['code' => 'USD']);
    $company->currency_id = $currency->id;
    $company->save();

    $user = \App\Models\User::factory()->create();

    $product = \Modules\Product\Models\Product::factory()->create([
        'company_id' => $company->id,
    ]);

    // Create Stock Location
    $location = \Modules\Inventory\Models\StockLocation::create([
        'company_id' => $company->id,
        'name' => 'Main Warehouse',
        'type' => \Modules\Inventory\Enums\Inventory\StockLocationType::Internal,
    ]);

    // Create a Stock Picking
    $picking = \Modules\Inventory\Models\StockPicking::create([
        'company_id' => $company->id,
        'type' => \Modules\Inventory\Enums\Inventory\StockPickingType::Receipt,
        'state' => \Modules\Inventory\Enums\Inventory\StockPickingState::Done,
        'scheduled_date' => now(),
        'created_by_user_id' => $user->id,
    ]);

    $move1 = \Modules\Inventory\Models\StockMove::create([
        'company_id' => $company->id,
        'picking_id' => $picking->id,
        'move_type' => \Modules\Inventory\Enums\Inventory\StockMoveType::InternalTransfer,
        'status' => \Modules\Inventory\Enums\Inventory\StockMoveStatus::Done,
        'move_date' => now(),
        'created_by_user_id' => $user->id,
    ]);

    // Create product line for the move to have quantity
    \Modules\Inventory\Models\StockMoveProductLine::create([
        'company_id' => $company->id,
        'stock_move_id' => $move1->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'from_location_id' => $location->id,
        'to_location_id' => $location->id,
    ]);

    // Create valuation for the move (initial cost)
    \Modules\Inventory\Models\StockMoveValuation::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'stock_move_id' => $move1->id,
        'quantity' => 10,
        'cost_impact' => 1000, // 10.00 in base currency minor units
        'valuation_method' => \Modules\Inventory\Enums\Inventory\ValuationMethod::STANDARD,
        'move_type' => \Modules\Inventory\Enums\Inventory\StockMoveType::InternalTransfer,
        'cost_source' => \Modules\Inventory\Enums\Inventory\CostSource::Manual,
        'source_type' => \Modules\Inventory\Models\StockMove::class,
        'source_id' => $move1->id,
    ]);

    // 2. Create Landed Cost
    $dto = new LandedCostData(
        company: $company,
        date: now(),
        amount_total: \Brick\Money\Money::of(100, $currency->code), // 100.00 additional cost
        allocation_method: LandedCostAllocationMethod::ByQuantity,
        description: 'Test Landed Cost',
        created_by_user: $user,
    );

    $action = app(CreateLandedCostAction::class);
    $landedCost = $action->execute($dto);

    expect($landedCost)->toBeInstanceOf(LandedCost::class);
    expect($landedCost->amount_total->getAmount()->toFloat())->toBe(100.0);

    // 3. Attach Stock Picking to Landed Cost (NEW WORKFLOW)
    $landedCost->stockPickings()->attach($picking->id);

    expect($landedCost->stockPickings)->toHaveCount(1);

    // 4. Get stock moves from attached pickings (simulating the Post action workflow)
    $stockMoves = $landedCost->stockPickings()
        ->with('moves')
        ->get()
        ->pluck('moves')
        ->flatten();

    // 5. Allocate Costs
    $allocateAction = app(AllocateLandedCostsAction::class);
    $allocateAction->execute($landedCost, $stockMoves);

    $landedCost->refresh();
    expect($landedCost->lines)->toHaveCount(1);
    expect($landedCost->lines->first()->additional_cost->getAmount()->toFloat())->toBe(100.0);

    // 6. Post Landed Cost
    $postAction = app(PostLandedCostAction::class);
    $postAction->execute($landedCost);

    $landedCost->refresh();

    expect($landedCost->status)->toBe(\Modules\Inventory\Enums\Inventory\LandedCostStatus::Posted);

    // Check StockMoveValuation created
    $valuation = StockMoveValuation::where('source_type', LandedCost::class)
        ->where('source_id', $landedCost->id)
        ->first();

    expect($valuation)->not->toBeNull();
    expect($valuation->cost_impact->getAmount()->toFloat())->toBe(100.0);
});
