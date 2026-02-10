<?php

namespace Kezi\Inventory\Tests\Feature\Actions\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Inventory\Actions\Inventory\ReceiveTransferAction;
use Kezi\Inventory\DataTransferObjects\Inventory\ReceiveTransferDTO;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Enums\Inventory\StockPickingState;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockMoveProductLine;
use Kezi\Inventory\Models\StockPicking;
use Kezi\Inventory\Models\StockQuant;
use Kezi\Inventory\Models\StockReservation;
use Kezi\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();
    $this->action = app(ReceiveTransferAction::class);
});

it('receives a transfer from transit location', function () {
    // Setup Transit and Destination Locations
    $transitLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockLocationType::Internal,
    ]);
    $destinationLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockLocationType::Internal,
    ]);

    // Create Stock Picking with Shipped state
    $picking = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'state' => StockPickingState::Shipped,
        'transit_location_id' => $transitLocation->id,
        'destination_location_id' => $destinationLocation->id,
    ]);

    // Create existing move (partially confirmed/shipped)
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $sourceLocation = $this->stockLocation;

    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'picking_id' => $picking->id,
        'move_type' => StockMoveType::Outgoing,
        'status' => StockMoveStatus::Confirmed,
    ]);

    $productLine = StockMoveProductLine::factory()->create([
        'stock_move_id' => $move->id,
        'product_id' => $product->id,
        'quantity' => 7,
        'from_location_id' => $sourceLocation->id,
        'to_location_id' => $destinationLocation->id,
        'company_id' => $this->company->id,
    ]);

    // Provide initial stock at transit
    app(\Kezi\Inventory\Services\Inventory\StockQuantService::class)->adjust(
        $this->company->id,
        $product->id,
        $transitLocation->id,
        10.0
    );

    // Create a reservation for this move
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'location_id' => $sourceLocation->id,
        'quantity' => 10,
        'reserved_quantity' => 7,
    ]);

    StockReservation::create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'stock_move_id' => $move->id,
        'location_id' => $sourceLocation->id,
        'quantity' => 7,
    ]);

    $dto = new ReceiveTransferDTO(
        stock_picking_id: $picking->id,
        received_by_user_id: $this->user->id
    );

    // Execute Action
    $receivedPicking = $this->action->execute($picking, $dto, $this->user);

    expect($receivedPicking->state)->toBe(StockPickingState::Done)
        ->and($receivedPicking->received_at)->not->toBeNull()
        ->and($receivedPicking->received_by_user_id)->toBe($this->user->id)
        ->and($receivedPicking->completed_at)->not->toBeNull();

    // Verify a new Receive Move was created (Transit -> Destination)
    $receiveMove = StockMove::where('source_type', StockPicking::class)
        ->where('source_id', $picking->id)
        ->where('move_type', StockMoveType::InternalTransfer)
        ->where('reference', 'LIKE', 'RECV-%')
        ->first();

    expect($receiveMove)->not->toBeNull()
        ->and($receiveMove->status)->toBe(StockMoveStatus::Done);

    // Verify Receive Move Line
    $receiveLine = $receiveMove->productLines->first();
    expect($receiveLine->product_id)->toBe($product->id)
        ->and($receiveLine->from_location_id)->toBe($transitLocation->id)
        ->and($receiveLine->to_location_id)->toBe($destinationLocation->id)
        ->and($receiveLine->quantity)->toEqual(7.0);

    // Verify original move is now Done
    $move->refresh();
    expect($move->status)->toBe(StockMoveStatus::Done);

    // Verify reservation was released
    expect(StockReservation::where('stock_move_id', $move->id)->count())->toBe(0);

    // Verify quant reservation was released
    $quant = StockQuant::where('product_id', $product->id)
        ->where('location_id', $sourceLocation->id)
        ->first();
    expect($quant->reserved_quantity)->toEqual(0.0);
});
