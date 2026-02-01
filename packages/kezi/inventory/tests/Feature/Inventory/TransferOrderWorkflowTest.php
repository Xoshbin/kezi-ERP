<?php

use App\Models\Company;
use App\Models\User;
use Kezi\Foundation\Models\Currency;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateTransferDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateTransferLineDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\ReceiveTransferDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\ShipTransferDTO;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Enums\Inventory\StockPickingState;
use Kezi\Inventory\Enums\Inventory\StockPickingType;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockPicking;
use Kezi\Inventory\Services\Inventory\TransferOrderService;
use Kezi\Product\Models\Product;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->company = Company::factory()->create();
    $this->currency = Currency::firstOrCreate(
        ['code' => 'USD'],
        ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true]
    );
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

    $this->service = app(TransferOrderService::class);
});

it('creates a transfer order in draft state', function () {
    $dto = new CreateTransferDTO(
        company_id: $this->company->id,
        source_location_id: $this->sourceLocation->id,
        destination_location_id: $this->destLocation->id,
        transit_location_id: $this->transitLocation->id,
        created_by_user_id: $this->user->id,
        lines: [
            new CreateTransferLineDTO(
                product_id: $this->product->id,
                quantity: 10.0,
            ),
        ],
    );

    $transfer = $this->service->create($dto);

    expect($transfer)
        ->toBeInstanceOf(StockPicking::class)
        ->type->toBe(StockPickingType::Internal)
        ->state->toBe(StockPickingState::Draft)
        ->transit_location_id->toBe($this->transitLocation->id)
        ->destination_location_id->toBe($this->destLocation->id);

    expect($transfer->stockMoves)->toHaveCount(1);
    expect($transfer->stockMoves->first()->productLines)->toHaveCount(1);
});

it('confirms transfer order and updates state', function () {
    $dto = new CreateTransferDTO(
        company_id: $this->company->id,
        source_location_id: $this->sourceLocation->id,
        destination_location_id: $this->destLocation->id,
        transit_location_id: $this->transitLocation->id,
        created_by_user_id: $this->user->id,
        lines: [
            new CreateTransferLineDTO(
                product_id: $this->product->id,
                quantity: 10.0,
            ),
        ],
    );

    $transfer = $this->service->create($dto);
    $confirmedTransfer = $this->service->confirm($transfer, $this->user);

    expect($confirmedTransfer->state)->toBe(StockPickingState::Confirmed);
});

it('ships transfer order and moves stock to transit', function () {
    $dto = new CreateTransferDTO(
        company_id: $this->company->id,
        source_location_id: $this->sourceLocation->id,
        destination_location_id: $this->destLocation->id,
        transit_location_id: $this->transitLocation->id,
        created_by_user_id: $this->user->id,
        lines: [
            new CreateTransferLineDTO(
                product_id: $this->product->id,
                quantity: 10.0,
            ),
        ],
    );

    $transfer = $this->service->create($dto);
    $this->service->confirm($transfer, $this->user);

    $shipDto = new ShipTransferDTO(
        stock_picking_id: $transfer->id,
        shipped_by_user_id: $this->user->id,
    );

    $shippedTransfer = $this->service->ship($transfer->fresh(), $shipDto, $this->user);

    expect($shippedTransfer)
        ->state->toBe(StockPickingState::Shipped)
        ->shipped_at->not->toBeNull()
        ->shipped_by_user_id->toBe($this->user->id);
});

it('receives transfer order and moves stock to destination', function () {
    $dto = new CreateTransferDTO(
        company_id: $this->company->id,
        source_location_id: $this->sourceLocation->id,
        destination_location_id: $this->destLocation->id,
        transit_location_id: $this->transitLocation->id,
        created_by_user_id: $this->user->id,
        lines: [
            new CreateTransferLineDTO(
                product_id: $this->product->id,
                quantity: 10.0,
            ),
        ],
    );

    $transfer = $this->service->create($dto);
    $this->service->confirm($transfer, $this->user);

    $shipDto = new ShipTransferDTO(
        stock_picking_id: $transfer->id,
        shipped_by_user_id: $this->user->id,
    );
    $shippedTransfer = $this->service->ship($transfer->fresh(), $shipDto, $this->user);

    $receiveDto = new ReceiveTransferDTO(
        stock_picking_id: $shippedTransfer->id,
        received_by_user_id: $this->user->id,
    );

    $receivedTransfer = $this->service->receive($shippedTransfer, $receiveDto, $this->user);

    expect($receivedTransfer)
        ->state->toBe(StockPickingState::Done)
        ->received_at->not->toBeNull()
        ->received_by_user_id->toBe($this->user->id)
        ->completed_at->not->toBeNull();
});

it('cancels confirmed transfer order', function () {
    $dto = new CreateTransferDTO(
        company_id: $this->company->id,
        source_location_id: $this->sourceLocation->id,
        destination_location_id: $this->destLocation->id,
        transit_location_id: $this->transitLocation->id,
        created_by_user_id: $this->user->id,
        lines: [
            new CreateTransferLineDTO(
                product_id: $this->product->id,
                quantity: 10.0,
            ),
        ],
    );

    $transfer = $this->service->create($dto);
    $this->service->confirm($transfer, $this->user);

    $cancelledTransfer = $this->service->cancel($transfer->fresh(), $this->user);

    expect($cancelledTransfer->state)->toBe(StockPickingState::Cancelled);
});

it('prevents shipping when transfer is not confirmed', function () {
    $dto = new CreateTransferDTO(
        company_id: $this->company->id,
        source_location_id: $this->sourceLocation->id,
        destination_location_id: $this->destLocation->id,
        transit_location_id: $this->transitLocation->id,
        created_by_user_id: $this->user->id,
        lines: [
            new CreateTransferLineDTO(
                product_id: $this->product->id,
                quantity: 10.0,
            ),
        ],
    );

    $transfer = $this->service->create($dto);

    $shipDto = new ShipTransferDTO(
        stock_picking_id: $transfer->id,
        shipped_by_user_id: $this->user->id,
    );

    expect(fn () => $this->service->ship($transfer, $shipDto, $this->user))
        ->toThrow(RuntimeException::class, 'Transfer cannot be shipped in its current state.');
});

it('prevents receiving when transfer is not shipped', function () {
    $dto = new CreateTransferDTO(
        company_id: $this->company->id,
        source_location_id: $this->sourceLocation->id,
        destination_location_id: $this->destLocation->id,
        transit_location_id: $this->transitLocation->id,
        created_by_user_id: $this->user->id,
        lines: [
            new CreateTransferLineDTO(
                product_id: $this->product->id,
                quantity: 10.0,
            ),
        ],
    );

    $transfer = $this->service->create($dto);
    $this->service->confirm($transfer, $this->user);

    $receiveDto = new ReceiveTransferDTO(
        stock_picking_id: $transfer->id,
        received_by_user_id: $this->user->id,
    );

    expect(fn () => $this->service->receive($transfer->fresh(), $receiveDto, $this->user))
        ->toThrow(RuntimeException::class, 'Transfer cannot be received in its current state.');
});

it('prevents cancelling shipped transfer', function () {
    $dto = new CreateTransferDTO(
        company_id: $this->company->id,
        source_location_id: $this->sourceLocation->id,
        destination_location_id: $this->destLocation->id,
        transit_location_id: $this->transitLocation->id,
        created_by_user_id: $this->user->id,
        lines: [
            new CreateTransferLineDTO(
                product_id: $this->product->id,
                quantity: 10.0,
            ),
        ],
    );

    $transfer = $this->service->create($dto);
    $this->service->confirm($transfer, $this->user);

    $shipDto = new ShipTransferDTO(
        stock_picking_id: $transfer->id,
        shipped_by_user_id: $this->user->id,
    );
    $this->service->ship($transfer->fresh(), $shipDto, $this->user);

    expect(fn () => $this->service->cancel($transfer->fresh(), $this->user))
        ->toThrow(RuntimeException::class, 'Shipped or completed transfers cannot be cancelled.');
});
