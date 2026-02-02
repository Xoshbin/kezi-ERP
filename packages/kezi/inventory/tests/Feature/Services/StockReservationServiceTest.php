<?php

namespace Kezi\Inventory\Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Models\Lot;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockQuant;
use Kezi\Inventory\Models\StockReservation;
use Kezi\Inventory\Services\Inventory\StockQuantService;
use Kezi\Inventory\Services\Inventory\StockReservationService;
use Kezi\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->stockQuantService = app(StockQuantService::class);
    $this->service = app(StockReservationService::class);

    $this->sourceLocation = StockLocation::factory()->for($this->company)->create(['type' => 'internal']);
    $this->destLocation = StockLocation::factory()->for($this->company)->create(['type' => 'customer']);

    $this->product = Product::factory()->for($this->company)->create([
        'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
    ]);
});

it('reserves available stock for a move', function () {
    // Setup initial stock: 100 units
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->sourceLocation->id,
        'quantity' => 100,
        'reserved_quantity' => 0,
    ]);

    // Create a move for 50 units
    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Outgoing,
        'status' => StockMoveStatus::Draft,
        // Factory handles product line creation via create() hook
        'product_id' => $this->product->id,
        'quantity' => 50,
        'from_location_id' => $this->sourceLocation->id,
        'to_location_id' => $this->destLocation->id,
    ]);

    $reserved = $this->service->reserveForMove($move, $this->sourceLocation->id);

    expect($reserved)->toBe(50.0);

    // Verify reservation record
    $reservation = StockReservation::where('stock_move_id', $move->id)->first();
    expect($reservation)->not->toBeNull()
        ->and((float) $reservation->quantity)->toBe(50.0)
        ->and($reservation->location_id)->toBe($this->sourceLocation->id);

    // Verify quant reserved quantity
    $quant = StockQuant::where('product_id', $this->product->id)
        ->where('location_id', $this->sourceLocation->id)
        ->first();
    expect($quant->reserved_quantity)->toBe(50.0);
});

it('partially reserves when stock is insufficient', function () {
    // Setup initial stock: 30 units
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->sourceLocation->id,
        'quantity' => 30,
        'reserved_quantity' => 0,
    ]);

    // Create a move for 50 units
    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Outgoing,
        'status' => StockMoveStatus::Draft,
        'product_id' => $this->product->id,
        'quantity' => 50,
        'from_location_id' => $this->sourceLocation->id,
        'to_location_id' => $this->destLocation->id,
    ]);

    $reserved = $this->service->reserveForMove($move, $this->sourceLocation->id);

    expect($reserved)->toBe(30.0);

    // Verify reservation record
    $reservation = StockReservation::where('stock_move_id', $move->id)->first();
    expect((float) $reservation->quantity)->toBe(30.0);

    // Verify quant reserved quantity
    $quant = StockQuant::where('product_id', $this->product->id)
        ->where('location_id', $this->sourceLocation->id)
        ->first();
    expect($quant->reserved_quantity)->toBe(30.0);
});

it('is idempotent (does not double reserve)', function () {
    // Setup initial stock: 100 units
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->sourceLocation->id,
        'quantity' => 100,
        'reserved_quantity' => 0,
    ]);

    // Create a move for 50 units
    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Outgoing,
        'status' => StockMoveStatus::Draft,
        'product_id' => $this->product->id,
        'quantity' => 50,
        'from_location_id' => $this->sourceLocation->id,
        'to_location_id' => $this->destLocation->id,
    ]);

    // First reservation
    $reserved1 = $this->service->reserveForMove($move, $this->sourceLocation->id);
    expect($reserved1)->toBe(50.0);

    // Second reservation call
    $reserved2 = $this->service->reserveForMove($move, $this->sourceLocation->id);
    expect($reserved2)->toBe(50.0);

    // Verify quant reserved quantity is still 50, not 100
    $quant = StockQuant::where('product_id', $this->product->id)
        ->where('location_id', $this->sourceLocation->id)
        ->first();
    expect($quant->reserved_quantity)->toBe(50.0);

    // Verify only one reservation record
    expect(StockReservation::where('stock_move_id', $move->id)->count())->toBe(1);
});

it('allocates lots using FEFO (First Expired First Out)', function () {
    // Create lots
    $lot1 = Lot::factory()->for($this->company)->create([
        'product_id' => $this->product->id,
        'lot_code' => 'LOT-001',
        'expiration_date' => now()->addDays(10),
    ]);

    $lot2 = Lot::factory()->for($this->company)->create([
        'product_id' => $this->product->id,
        'lot_code' => 'LOT-002',
        'expiration_date' => now()->addDays(5), // Expires sooner
    ]);

    // Add stock for Lot 1 (100 units)
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->sourceLocation->id,
        'lot_id' => $lot1->id,
        'quantity' => 100,
        'reserved_quantity' => 0,
    ]);

    // Add stock for Lot 2 (50 units) - Should be picked first
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->sourceLocation->id,
        'lot_id' => $lot2->id,
        'quantity' => 50,
        'reserved_quantity' => 0,
    ]);

    // Request 60 units. Should take 50 from Lot 2, and 10 from Lot 1.
    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Outgoing,
        'move_type' => StockMoveType::Outgoing,
        'move_type' => StockMoveType::Outgoing,
        'status' => StockMoveStatus::Draft,
        'product_id' => $this->product->id,
        'quantity' => 60,
        'from_location_id' => $this->sourceLocation->id,
        'to_location_id' => $this->destLocation->id,
    ]);

    $reserved = $this->service->reserveForMove($move, $this->sourceLocation->id);
    expect($reserved)->toBe(60.0);

    // Verify Lot 2 (expires sooner) is fully reserved
    $quantLot2 = StockQuant::where('lot_id', $lot2->id)->first();
    expect($quantLot2->reserved_quantity)->toBe(50.0);

    // Verify Lot 1 is partially reserved
    $quantLot1 = StockQuant::where('lot_id', $lot1->id)->first();
    expect($quantLot1->reserved_quantity)->toBe(10.0);
});

it('consumes reservations and decreases stock', function () {
    // Setup initial stock: 100 units
    $quant = StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->sourceLocation->id,
        'quantity' => 100,
        'reserved_quantity' => 0,
    ]);

    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Outgoing,
        'move_type' => StockMoveType::Outgoing,
        'status' => StockMoveStatus::Draft,
        'product_id' => $this->product->id,
        'quantity' => 40,
        'from_location_id' => $this->sourceLocation->id,
        'to_location_id' => $this->destLocation->id,
    ]);

    // Reserve first
    $this->service->reserveForMove($move, $this->sourceLocation->id);

    // Verify reserved
    $quant->refresh();
    expect($quant->reserved_quantity)->toBe(40.0);

    // Consume
    $consumed = $this->service->consumeForMove($move);
    expect($consumed)->toBe(40.0);

    // Verify stock decreased and reservation cleared
    $quant->refresh();
    expect($quant->quantity)->toBe(60.0); // 100 - 40
    expect($quant->reserved_quantity)->toBe(0.0);

    // Verify reservation record is gone
    expect(StockReservation::where('stock_move_id', $move->id)->count())->toBe(0);
});

it('releases reservations without consuming stock', function () {
    // Setup initial stock: 100 units
    $quant = StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->sourceLocation->id,
        'quantity' => 100,
        'reserved_quantity' => 0,
    ]);

    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Outgoing,
        'status' => StockMoveStatus::Draft,
        'product_id' => $this->product->id,
        'quantity' => 40,
        'from_location_id' => $this->sourceLocation->id,
        'to_location_id' => $this->destLocation->id,
    ]);

    // Reserve
    $this->service->reserveForMove($move, $this->sourceLocation->id);
    $quant->refresh();
    expect($quant->reserved_quantity)->toBe(40.0);

    // Release (e.g. cancelling the move)
    $this->service->releaseForMove($move);

    // Verify reservation record is gone
    expect(StockReservation::where('stock_move_id', $move->id)->count())->toBe(0);

    // Check if quant reserved quantity is updated (should be back to 0)
    $quant->refresh();
    expect($quant->reserved_quantity)->toBe(0.0);
});

it('updates available quantity in StockQuantService after reservation', function () {
    // Setup initial stock: 100 units
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->sourceLocation->id,
        'quantity' => 100,
        'reserved_quantity' => 0,
    ]);

    expect($this->stockQuantService->available($this->company->id, $this->product->id, $this->sourceLocation->id))->toBe(100.0);

    // Create a move for 40 units
    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Outgoing,
        'status' => StockMoveStatus::Draft,
        'product_id' => $this->product->id,
        'quantity' => 40,
        'from_location_id' => $this->sourceLocation->id,
        'to_location_id' => $this->destLocation->id,
    ]);

    $this->service->reserveForMove($move, $this->sourceLocation->id);

    // Check available quantity
    expect($this->stockQuantService->available($this->company->id, $this->product->id, $this->sourceLocation->id))->toBe(60.0);

    // Release
    $this->service->releaseForMove($move);
    expect($this->stockQuantService->available($this->company->id, $this->product->id, $this->sourceLocation->id))->toBe(100.0);
});
