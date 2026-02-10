<?php

use Kezi\Inventory\DataTransferObjects\Inventory\CreateTransferDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateTransferLineDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\ReceiveTransferDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\ShipTransferDTO;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockQuant;
use Kezi\Inventory\Models\StockReservation;
use Kezi\Inventory\Services\Inventory\StockQuantService;
use Kezi\Inventory\Services\Inventory\TransferOrderService;
use Kezi\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    $this->service = app(TransferOrderService::class);
    $this->stockQuantService = app(StockQuantService::class);

    $this->product = Product::factory()->for($this->company)->create();

    $this->sourceLocation = StockLocation::factory()->for($this->company)->create([
        'type' => StockLocationType::Internal,
        'name' => 'Source',
    ]);

    $this->destLocation = StockLocation::factory()->for($this->company)->create([
        'type' => StockLocationType::Internal,
        'name' => 'Destination',
    ]);

    $this->transitLocation = StockLocation::factory()->for($this->company)->create([
        'type' => StockLocationType::Transit,
        'name' => 'Transit',
    ]);
});

function assertStock(float $quantity, float $reserved, StockLocation $location, $company, $product)
{
    $quant = StockQuant::where('company_id', $company->id)
        ->where('product_id', $product->id)
        ->where('location_id', $location->id)
        ->first();

    $actualQty = $quant ? (float) $quant->quantity : 0.0;
    $actualReserved = $quant ? (float) $quant->reserved_quantity : 0.0;

    expect($actualQty)->toBe($quantity, "Quantity mismatch at {$location->name}");
    expect($actualReserved)->toBe($reserved, "Reserved quantity mismatch at {$location->name}");
}

it('moves stock through all stages including reservations', function () {
    // 1. Initial stock at source
    $this->stockQuantService->adjust($this->company->id, $this->product->id, $this->sourceLocation->id, 100.0);

    $dto = new CreateTransferDTO(
        company_id: $this->company->id,
        source_location_id: $this->sourceLocation->id,
        destination_location_id: $this->destLocation->id,
        transit_location_id: $this->transitLocation->id,
        created_by_user_id: $this->user->id,
        lines: [
            new CreateTransferLineDTO(
                product_id: $this->product->id,
                quantity: 40.0,
            ),
        ],
    );

    $transfer = $this->service->create($dto);

    // Initial state
    assertStock(100.0, 0.0, $this->sourceLocation, $this->company, $this->product);

    // 2. Confirm -> Reserve
    $this->service->confirm($transfer, $this->user);
    assertStock(100.0, 40.0, $this->sourceLocation, $this->company, $this->product);
    expect(StockReservation::where('stock_move_id', $transfer->stockMoves->first()->id)->exists())->toBeTrue();

    // 3. Ship -> Should consume reservation at source and move to transit
    $shipDto = new ShipTransferDTO(
        stock_picking_id: $transfer->id,
        shipped_by_user_id: $this->user->id,
    );
    $this->service->ship($transfer->fresh(), $shipDto, $this->user);

    // Check source: Should be 60/0
    assertStock(60.0, 0.0, $this->sourceLocation, $this->company, $this->product);
    // Check transit: Should be 40/0
    assertStock(40.0, 0.0, $this->transitLocation, $this->company, $this->product);

    // 4. Receive -> Move from transit to destination
    $receiveDto = new ReceiveTransferDTO(
        stock_picking_id: $transfer->id,
        received_by_user_id: $this->user->id,
    );
    $this->service->receive($transfer->fresh(), $receiveDto, $this->user);

    // Check transit: Should be 0/0
    assertStock(0.0, 0.0, $this->transitLocation, $this->company, $this->product);
    // Check destination: Should be 40/0
    assertStock(40.0, 0.0, $this->destLocation, $this->company, $this->product);
});
