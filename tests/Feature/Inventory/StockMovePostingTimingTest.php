<?php

use App\Actions\Inventory\CreateStockMoveAction;
use App\Actions\Inventory\UpdateStockMoveWithProductLinesAction;
use App\DataTransferObjects\Inventory\CreateStockMoveDTO;
use App\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use App\DataTransferObjects\Inventory\UpdateStockMoveWithProductLinesDTO;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Models\Product;
use App\Models\StockMove;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    // Create a storable product with required inventory accounts and valid average cost
    $this->product = Product::factory()->for($this->company)->create([
        'type' => \App\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => \App\Enums\Inventory\ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'average_cost' => \Brick\Money\Money::of(100, $this->company->currency->code), // Valid cost for testing
    ]);
});

it('creates journal entries when creating a stock move directly as done', function () {
    // Arrange: Create a stock move DTO with status Done (manual receipt)
    $lineDto = new CreateStockMoveProductLineDTO(
        product_id: $this->product->id,
        quantity: 3.0,
        from_location_id: $this->vendorLocation->id,
        to_location_id: $this->stockLocation->id,
        description: 'Manual receipt',
        source_type: 'Test',
        source_id: 1,
    );

    $dto = new CreateStockMoveDTO(
        company_id: $this->company->id,
        move_type: StockMoveType::Incoming,
        status: StockMoveStatus::Done, // critical: set to done at creation
        move_date: now(),
        created_by_user_id: $this->user->id,
        product_lines: [$lineDto],
        reference: 'SM-DONE-ON-CREATE',
        description: 'Test posting on create',
        source_type: 'Test',
        source_id: 1,
    );

    // Act
    $move = app(CreateStockMoveAction::class)->execute($dto);
    $move->refresh();

    // Assert
    expect($move)->toBeInstanceOf(StockMove::class);
    expect($move->status)->toBe(StockMoveStatus::Done);
    expect($move->stockMoveValuations()->exists())->toBeTrue();
});

it('creates journal entries when updating a draft stock move to done', function () {
    // Arrange: Create initial draft move
    $lineDto = new CreateStockMoveProductLineDTO(
        product_id: $this->product->id,
        quantity: 2.0,
        from_location_id: $this->vendorLocation->id,
        to_location_id: $this->stockLocation->id,
        description: 'Manual receipt draft',
        source_type: 'Test',
        source_id: 2,
    );

    $createDto = new CreateStockMoveDTO(
        company_id: $this->company->id,
        move_type: StockMoveType::Incoming,
        status: StockMoveStatus::Draft,
        move_date: now(),
        created_by_user_id: $this->user->id,
        product_lines: [$lineDto],
        reference: 'SM-DRAFT',
        description: 'Draft move',
        source_type: 'Test',
        source_id: 2,
    );

    $move = app(CreateStockMoveAction::class)->execute($createDto);

    // Act: Update to done using the update action with lines
    $updateDto = new UpdateStockMoveWithProductLinesDTO(
        id: $move->id,
        move_type: StockMoveType::Incoming,
        status: StockMoveStatus::Done,
        move_date: now(),
        product_lines: [$lineDto],
        reference: 'SM-DRAFT-TO-DONE',
        description: 'Posted move',
        source_type: 'Test',
        source_id: 2,
    );

    $move = app(UpdateStockMoveWithProductLinesAction::class)->execute($updateDto);
    $move->refresh();

    // Assert
    expect($move->status)->toBe(StockMoveStatus::Done);
    expect($move->stockMoveValuations()->exists())->toBeTrue();
});
