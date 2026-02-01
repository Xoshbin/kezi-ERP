<?php

namespace Jmeryar\Inventory\Tests\Feature\Actions\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Inventory\Actions\Inventory\CreateStockMoveAction;
use Jmeryar\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Jmeryar\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use Jmeryar\Inventory\Enums\Inventory\StockMoveStatus;
use Jmeryar\Inventory\Enums\Inventory\StockMoveType;
use Jmeryar\Inventory\Models\StockLocation;
use Jmeryar\Inventory\Models\StockMove;
use Jmeryar\Product\Models\Product;
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
