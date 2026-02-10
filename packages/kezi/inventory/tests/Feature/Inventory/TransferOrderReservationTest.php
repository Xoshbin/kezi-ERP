<?php

use App\Models\Company;
use App\Models\User;
use Kezi\Foundation\Models\Currency;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateTransferDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateTransferLineDTO;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockQuant;
use Kezi\Inventory\Models\StockReservation;
use Kezi\Inventory\Services\Inventory\StockQuantService;
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
    $this->stockQuantService = app(StockQuantService::class);
});

it('reserves stock when confirming transfer order', function () {
    // Add initial stock
    $this->stockQuantService->adjust(
        $this->company->id,
        $this->product->id,
        $this->sourceLocation->id,
        100.0
    );

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

    // Assert reservation created
    $reservation = StockReservation::where('stock_move_id', $transfer->stockMoves->first()->id)->first();
    expect($reservation)->not->toBeNull()
        ->quantity->toEqual(10.0)
        ->location_id->toBe($this->sourceLocation->id);

    // Assert quant reserved quantity updated
    $quant = StockQuant::where('company_id', $this->company->id)
        ->where('product_id', $this->product->id)
        ->where('location_id', $this->sourceLocation->id)
        ->first();

    expect($quant->reserved_quantity)->toBe(10.0);
});

it('releases stock reservation when cancelling transfer order', function () {
    // Add initial stock
    $this->stockQuantService->adjust(
        $this->company->id,
        $this->product->id,
        $this->sourceLocation->id,
        100.0
    );

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

    // Confirm reservation exists
    expect(StockReservation::where('stock_move_id', $transfer->stockMoves->first()->id)->exists())->toBeTrue();

    // Cancel transfer
    $this->service->cancel($transfer->fresh(), $this->user);

    // Assert reservation deleted
    expect(StockReservation::where('stock_move_id', $transfer->stockMoves->first()->id)->exists())->toBeFalse();

    // Assert quant reserved quantity released
    $quant = StockQuant::where('company_id', $this->company->id)
        ->where('product_id', $this->product->id)
        ->where('location_id', $this->sourceLocation->id)
        ->first();

    expect($quant->reserved_quantity)->toBe(0.0);
});
