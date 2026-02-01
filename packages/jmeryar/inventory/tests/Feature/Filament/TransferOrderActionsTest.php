<?php

use App\Models\Company;
use App\Models\User;
use Livewire\Livewire;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Inventory\Enums\Inventory\StockLocationType;
use Jmeryar\Inventory\Enums\Inventory\StockMoveStatus;
use Jmeryar\Inventory\Enums\Inventory\StockPickingState;
use Jmeryar\Inventory\Enums\Inventory\StockPickingType;
use Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages\ViewStockPicking;
use Jmeryar\Inventory\Models\StockLocation;
use Jmeryar\Inventory\Models\StockPicking;
use Jmeryar\Product\Models\Product;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->company = Company::factory()->create();
    $this->currency = Currency::factory()->create(['code' => 'USD']);
    $this->company->update(['currency_id' => $this->currency->id]);

    // Set tenant context
    filament()->setTenant($this->company);

    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->sourceLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Source Warehouse',
        'type' => StockLocationType::Internal,
    ]);

    $this->destLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Destination Warehouse',
        'type' => StockLocationType::Internal,
    ]);

    $this->transitLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'In Transit',
        'type' => StockLocationType::Transit,
    ]);
});

it('displays ship action for confirmed internal transfer', function () {
    $transfer = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockPickingType::Internal,
        'state' => StockPickingState::Confirmed,
        'transit_location_id' => $this->transitLocation->id,
        'destination_location_id' => $this->destLocation->id,
        'created_by_user_id' => $this->user->id,
    ]);

    // Create stock move with product line
    $move = $transfer->stockMoves()->create([
        'company_id' => $this->company->id,
        'move_type' => \Jmeryar\Inventory\Enums\Inventory\StockMoveType::InternalTransfer,
        'status' => StockMoveStatus::Confirmed,
        'move_date' => now(),
        'created_by_user_id' => $this->user->id,
    ]);

    $move->productLines()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'from_location_id' => $this->sourceLocation->id,
        'to_location_id' => $this->destLocation->id,
    ]);

    Livewire::test(ViewStockPicking::class, ['record' => $transfer->id])
        ->assertActionExists('ship')
        ->assertActionVisible('ship');
});

it('ships internal transfer via ship action', function () {
    $transfer = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockPickingType::Internal,
        'state' => StockPickingState::Confirmed,
        'transit_location_id' => $this->transitLocation->id,
        'destination_location_id' => $this->destLocation->id,
        'created_by_user_id' => $this->user->id,
    ]);

    // Create stock move with product line
    $move = $transfer->stockMoves()->create([
        'company_id' => $this->company->id,
        'move_type' => \Jmeryar\Inventory\Enums\Inventory\StockMoveType::InternalTransfer,
        'status' => StockMoveStatus::Confirmed,
        'move_date' => now(),
        'created_by_user_id' => $this->user->id,
    ]);

    $move->productLines()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'from_location_id' => $this->sourceLocation->id,
        'to_location_id' => $this->destLocation->id,
    ]);

    Livewire::test(ViewStockPicking::class, ['record' => $transfer->id])
        ->callAction('ship')
        ->assertNotified();

    $transfer->refresh();

    expect($transfer)
        ->state->toBe(StockPickingState::Shipped)
        ->shipped_at->not->toBeNull()
        ->shipped_by_user_id->toBe($this->user->id);
});

it('displays receive action for shipped internal transfer', function () {
    $transfer = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockPickingType::Internal,
        'state' => StockPickingState::Shipped,
        'transit_location_id' => $this->transitLocation->id,
        'destination_location_id' => $this->destLocation->id,
        'shipped_at' => now(),
        'shipped_by_user_id' => $this->user->id,
        'created_by_user_id' => $this->user->id,
    ]);

    // Create stock move with product line
    $move = $transfer->stockMoves()->create([
        'company_id' => $this->company->id,
        'move_type' => \Jmeryar\Inventory\Enums\Inventory\StockMoveType::InternalTransfer,
        'status' => StockMoveStatus::Confirmed,
        'move_date' => now(),
        'created_by_user_id' => $this->user->id,
    ]);

    $move->productLines()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'from_location_id' => $this->sourceLocation->id,
        'to_location_id' => $this->destLocation->id,
    ]);

    Livewire::test(ViewStockPicking::class, ['record' => $transfer->id])
        ->assertActionExists('receive')
        ->assertActionVisible('receive');
});

it('receives internal transfer via receive action', function () {
    $transfer = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockPickingType::Internal,
        'state' => StockPickingState::Shipped,
        'transit_location_id' => $this->transitLocation->id,
        'destination_location_id' => $this->destLocation->id,
        'shipped_at' => now(),
        'shipped_by_user_id' => $this->user->id,
        'created_by_user_id' => $this->user->id,
    ]);

    // Create stock move with product line
    $move = $transfer->stockMoves()->create([
        'company_id' => $this->company->id,
        'move_type' => \Jmeryar\Inventory\Enums\Inventory\StockMoveType::InternalTransfer,
        'status' => StockMoveStatus::Confirmed,
        'move_date' => now(),
        'created_by_user_id' => $this->user->id,
    ]);

    $move->productLines()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'from_location_id' => $this->sourceLocation->id,
        'to_location_id' => $this->destLocation->id,
    ]);

    Livewire::test(ViewStockPicking::class, ['record' => $transfer->id])
        ->callAction('receive')
        ->assertNotified();

    $transfer->refresh();

    expect($transfer)
        ->state->toBe(StockPickingState::Done)
        ->received_at->not->toBeNull()
        ->received_by_user_id->toBe($this->user->id)
        ->completed_at->not->toBeNull();
});

it('creates stock moves when shipping transfer', function () {
    $transfer = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockPickingType::Internal,
        'state' => StockPickingState::Confirmed,
        'transit_location_id' => $this->transitLocation->id,
        'destination_location_id' => $this->destLocation->id,
        'created_by_user_id' => $this->user->id,
    ]);

    // Create stock move with product line
    $move = $transfer->stockMoves()->create([
        'company_id' => $this->company->id,
        'move_type' => \Jmeryar\Inventory\Enums\Inventory\StockMoveType::InternalTransfer,
        'status' => StockMoveStatus::Confirmed,
        'move_date' => now(),
        'created_by_user_id' => $this->user->id,
    ]);

    $move->productLines()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'from_location_id' => $this->sourceLocation->id,
        'to_location_id' => $this->destLocation->id,
    ]);

    Livewire::test(ViewStockPicking::class, ['record' => $transfer->id])
        ->callAction('ship');

    $transfer->refresh();

    // Verify transfer was shipped
    expect($transfer->state)->toBe(StockPickingState::Shipped);
    expect($transfer->shipped_at)->not->toBeNull();
});

it('creates stock moves when receiving transfer', function () {
    $transfer = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockPickingType::Internal,
        'state' => StockPickingState::Shipped,
        'transit_location_id' => $this->transitLocation->id,
        'destination_location_id' => $this->destLocation->id,
        'shipped_at' => now(),
        'shipped_by_user_id' => $this->user->id,
        'created_by_user_id' => $this->user->id,
    ]);

    // Create stock move with product line
    $move = $transfer->stockMoves()->create([
        'company_id' => $this->company->id,
        'move_type' => \Jmeryar\Inventory\Enums\Inventory\StockMoveType::InternalTransfer,
        'status' => StockMoveStatus::Confirmed,
        'move_date' => now(),
        'created_by_user_id' => $this->user->id,
    ]);

    $move->productLines()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'from_location_id' => $this->sourceLocation->id,
        'to_location_id' => $this->destLocation->id,
    ]);

    Livewire::test(ViewStockPicking::class, ['record' => $transfer->id])
        ->callAction('receive');

    $transfer->refresh();

    // Verify transfer was received and completed
    expect($transfer->state)->toBe(StockPickingState::Done);
    expect($transfer->received_at)->not->toBeNull();
    expect($transfer->completed_at)->not->toBeNull();
});
