<?php

use App\Models\Company;
use App\Models\User;
use Modules\Product\Models\Product;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->product = Product::factory()->create(['company_id' => $this->company->id]);
    $this->fromLocation = StockLocation::factory()->create(['company_id' => $this->company->id]);
    $this->toLocation = StockLocation::factory()->create(['company_id' => $this->company->id]);
    $this->stockMoveService = app(StockMoveService::class);
});

it('can create a stock move with product lines', function () {
    $productLineDto = new CreateStockMoveProductLineDTO(
        product_id: $this->product->id,
        quantity: 10.0,
        from_location_id: $this->fromLocation->id,
        to_location_id: $this->toLocation->id,
        description: 'Test product line',
        source_type: 'Test',
        source_id: 1
    );

    $dto = new CreateStockMoveDTO(
        company_id: $this->company->id,
        product_lines: [$productLineDto],
        move_type: StockMoveType::Incoming,
        status: StockMoveStatus::Draft,
        move_date: now(),
        reference: 'SM-TEST-001',
        description: 'Test stock move',
        source_type: 'Test',
        source_id: 1,
        created_by_user_id: $this->user->id
    );

    $stockMove = $this->stockMoveService->createMove($dto);

    expect($stockMove)->toBeInstanceOf(StockMove::class);
    expect($stockMove->company_id)->toBe($this->company->id);
    expect($stockMove->move_type)->toBe(StockMoveType::Incoming);
    expect($stockMove->status)->toBe(StockMoveStatus::Draft);
    expect($stockMove->reference)->toBe('SM-TEST-001');
    expect($stockMove->description)->toBe('Test stock move');

    // Check that product line was created
    expect($stockMove->productLines)->toHaveCount(1);
    $productLine = $stockMove->productLines->first();
    expect($productLine)->toBeInstanceOf(StockMoveProductLine::class);
    expect($productLine->product_id)->toBe($this->product->id);
    expect((float) $productLine->quantity)->toBe(10.0);
    expect($productLine->from_location_id)->toBe($this->fromLocation->id);
    expect($productLine->to_location_id)->toBe($this->toLocation->id);
    expect($productLine->description)->toBe('Test product line');
});

it('can create a stock move with multiple product lines', function () {
    $product2 = Product::factory()->create(['company_id' => $this->company->id]);

    $productLineDto1 = new CreateStockMoveProductLineDTO(
        product_id: $this->product->id,
        quantity: 10.0,
        from_location_id: $this->fromLocation->id,
        to_location_id: $this->toLocation->id,
        description: 'First product line',
        source_type: 'Test',
        source_id: 1
    );

    $productLineDto2 = new CreateStockMoveProductLineDTO(
        product_id: $product2->id,
        quantity: 5.0,
        from_location_id: $this->fromLocation->id,
        to_location_id: $this->toLocation->id,
        description: 'Second product line',
        source_type: 'Test',
        source_id: 1
    );

    $dto = new CreateStockMoveDTO(
        company_id: $this->company->id,
        product_lines: [$productLineDto1, $productLineDto2],
        move_type: StockMoveType::Incoming,
        status: StockMoveStatus::Draft,
        move_date: now(),
        reference: 'SM-TEST-002',
        description: 'Test multi-product stock move',
        source_type: 'Test',
        source_id: 1,
        created_by_user_id: $this->user->id
    );

    $stockMove = $this->stockMoveService->createMove($dto);

    expect($stockMove->productLines)->toHaveCount(2);

    $productLines = $stockMove->productLines->sortBy('product_id');
    expect($productLines->first()->product_id)->toBe($this->product->id);
    expect((float) $productLines->first()->quantity)->toBe(10.0);
    expect($productLines->last()->product_id)->toBe($product2->id);
    expect((float) $productLines->last()->quantity)->toBe(5.0);
});
