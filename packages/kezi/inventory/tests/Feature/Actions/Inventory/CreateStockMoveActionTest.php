<?php

namespace Kezi\Inventory\Tests\Feature\Actions\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Inventory\Actions\Inventory\CreateStockMoveAction;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockMove;
use Kezi\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();
    $this->action = app(CreateStockMoveAction::class);
});

it('creates a draft stock move with product lines', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $fromLocation = StockLocation::factory()->create(['company_id' => $this->company->id]);
    $toLocation = StockLocation::factory()->create(['company_id' => $this->company->id]);

    $lineDto = new CreateStockMoveProductLineDTO(
        product_id: $product->id,
        quantity: 10,
        from_location_id: $fromLocation->id,
        to_location_id: $toLocation->id,
        description: 'Test Line'
    );

    $dto = new CreateStockMoveDTO(
        company_id: $this->company->id,
        move_type: StockMoveType::Incoming,
        status: StockMoveStatus::Draft,
        move_date: now(),
        created_by_user_id: $this->user->id,
        product_lines: [$lineDto],
        reference: 'REF-001',
        description: 'Test Description'
    );

    $move = $this->action->execute($dto);

    expect($move)->toBeInstanceOf(StockMove::class)
        ->and($move->status)->toBe(StockMoveStatus::Draft)
        ->and($move->reference)->toBe('REF-001')
        ->and($move->description)->toBe('Test Description')
        ->and($move->productLines)->toHaveCount(1);

    expect($move->productLines->first())
        ->product_id->toBe($product->id)
        ->quantity->toEqual(10.0)
        ->from_location_id->toBe($fromLocation->id)
        ->to_location_id->toBe($toLocation->id);

    $this->assertDatabaseHas('stock_moves', [
        'id' => $move->id,
        'status' => StockMoveStatus::Draft,
    ]);
});

it('creates a done stock move directly', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $fromLocation = StockLocation::factory()->create(['company_id' => $this->company->id]);
    $toLocation = StockLocation::factory()->create(['company_id' => $this->company->id]);

    $lineDto = new CreateStockMoveProductLineDTO(
        product_id: $product->id,
        quantity: 5,
        from_location_id: $fromLocation->id,
        to_location_id: $toLocation->id
    );

    $this->seedStock($product, $fromLocation, 10);

    $dto = new CreateStockMoveDTO(
        company_id: $this->company->id,
        move_type: StockMoveType::Outgoing,
        status: StockMoveStatus::Done,
        move_date: now(),
        created_by_user_id: $this->user->id,
        product_lines: [$lineDto]
    );

    $move = $this->action->execute($dto);

    expect($move->status)->toBe(StockMoveStatus::Done);

    $this->assertDatabaseHas('stock_moves', [
        'id' => $move->id,
        'status' => StockMoveStatus::Done,
    ]);
});
