<?php

namespace Kezi\Inventory\Tests\Feature\Actions\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Inventory\Actions\Inventory\ShipTransferAction;
use Kezi\Inventory\DataTransferObjects\Inventory\ShipTransferDTO;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Enums\Inventory\StockPickingState;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockMoveProductLine;
use Kezi\Inventory\Models\StockPicking;
use Kezi\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();
    $this->action = app(ShipTransferAction::class);
});

it('ships a transfer to transit location', function () {
    // Setup Transit Location
    $transitLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockLocationType::Internal,
    ]);

    // Create Stock Picking with Transit Location
    $picking = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'state' => StockPickingState::Confirmed,
        'transit_location_id' => $transitLocation->id,
    ]);

    // Create existing move (source -> destination)
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $sourceLocation = $this->stockLocation;
    $destinationLocation = $this->customerLocation;

    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'picking_id' => $picking->id,
        'move_type' => StockMoveType::Outgoing,
        'status' => StockMoveStatus::Confirmed,
    ]);

    $productLine = StockMoveProductLine::factory()->create([
        'stock_move_id' => $move->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'from_location_id' => $sourceLocation->id,
        'to_location_id' => $destinationLocation->id,
        'company_id' => $this->company->id,
    ]);

    $dto = new ShipTransferDTO(
        stock_picking_id: $picking->id,
        shipped_by_user_id: $this->user->id
    );

    // Execute Action
    $shippedPicking = $this->action->execute($picking, $dto, $this->user);

    expect($shippedPicking->state)->toBe(StockPickingState::Shipped)
        ->and($shippedPicking->shipped_at)->not->toBeNull()
        ->and($shippedPicking->shipped_by_user_id)->toBe($this->user->id);

    // Verify a new Ship Move was created (Source -> Transit)
    $shipMove = StockMove::where('source_type', StockPicking::class)
        ->where('source_id', $picking->id)
        ->where('move_type', StockMoveType::InternalTransfer)
        ->where('reference', 'LIKE', 'SHIP-%')
        ->first();

    expect($shipMove)->not->toBeNull()
        ->and($shipMove->status)->toBe(StockMoveStatus::Done);

    // Verify Ship Move Line
    $shipLine = $shipMove->productLines->first();
    expect($shipLine->product_id)->toBe($product->id)
        ->and($shipLine->from_location_id)->toBe($sourceLocation->id)
        ->and($shipLine->to_location_id)->toBe($transitLocation->id)
        ->and($shipLine->quantity)->toEqual(5.0);
});
