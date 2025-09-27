<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Account;
use Modules\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    // Ensure required accounts exist for product
    $this->inventoryAccount = Account::factory()->for($this->company)->create([
        'name' => 'Inventory Asset',
        'type' => 'current_assets',
    ]);
    $this->stockInputAccount = Account::factory()->for($this->company)->create([
        'name' => 'Stock Input',
        'type' => 'current_liabilities',
    ]);

    // Create a storable product with a non-zero average cost to be used for manual moves
    $this->product = Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'average_cost' => Money::of(120, $this->company->currency->code),
    ]);
});

it('creates non-zero inventory journal amounts for manual incoming stock moves', function () {
    // Arrange: manual incoming move set to Done
    $qty = 2.0;
    $lineDto = new CreateStockMoveProductLineDTO(
        product_id: $this->product->id,
        quantity: $qty,
        from_location_id: $this->vendorLocation->id,
        to_location_id: $this->stockLocation->id,
        description: 'Manual receipt',
        source_type: 'Test',
        source_id: 999,
    );

    $dto = new CreateStockMoveDTO(
        company_id: $this->company->id,
        move_type: StockMoveType::Incoming,
        status: StockMoveStatus::Done,
        move_date: now(),
        created_by_user_id: $this->user->id,
        product_lines: [$lineDto],
        reference: 'SM-MANUAL-TEST',
        description: 'Manual stock receipt with valuation',
        source_type: 'Test',
        source_id: 999,
    );

    // Act
    $move = app(\Modules\Inventory\Actions\Inventory\CreateStockMoveAction::class)->execute($dto);
    $move->refresh();

    // Assert: a valuation was created and linked to a journal entry with non-zero amounts
    expect($move)->toBeInstanceOf(StockMove::class);
    $valuation = StockMoveValuation::where('stock_move_id', $move->id)->first();
    expect($valuation)->not->toBeNull();

    $expectedCost = Money::of(120, $this->company->currency->code)->multipliedBy($qty);
    expect($valuation->cost_impact->isEqualTo($expectedCost))->toBeTrue();

    $journalEntry = JournalEntry::find($valuation->journal_entry_id);
    expect($journalEntry)->not->toBeNull();

    // Debit Inventory
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->inventoryAccount->id,
        'debit' => $expectedCost->getMinorAmount()->toInt(),
        'credit' => 0,
    ]);

    // Credit Stock Input
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->stockInputAccount->id,
        'debit' => 0,
        'credit' => $expectedCost->getMinorAmount()->toInt(),
    ]);
});

it('throws exception for manual stock moves when product has no cost information', function () {
    // Arrange: Create a product with zero average cost and no cost layers
    $productWithoutCost = Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'average_cost' => Money::of(0, $this->company->currency->code), // Zero average cost
    ]);

    $lineDto = new CreateStockMoveProductLineDTO(
        product_id: $productWithoutCost->id,
        quantity: 1.0,
        from_location_id: $this->vendorLocation->id,
        to_location_id: $this->stockLocation->id,
        description: 'Manual receipt without cost',
        source_type: 'Test',
        source_id: 999,
    );

    $dto = new CreateStockMoveDTO(
        company_id: $this->company->id,
        move_type: StockMoveType::Incoming,
        status: StockMoveStatus::Done,
        move_date: now(),
        created_by_user_id: $this->user->id,
        product_lines: [$lineDto],
        reference: 'SM-NO-COST',
        description: 'Manual stock receipt without cost info',
        source_type: 'Test',
        source_id: 999,
    );

    // Act & Assert: Should throw InsufficientCostInformationException
    expect(fn() => app(\Modules\Inventory\Actions\Inventory\CreateStockMoveAction::class)->execute($dto))
        ->toThrow(InsufficientCostInformationException::class, 'Cannot determine cost for product');
});

it('throws exception for manual outgoing stock moves when product has no cost information', function () {
    // Arrange: Create a product with zero average cost and no cost layers
    $productWithoutCost = Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'average_cost' => Money::of(0, $this->company->currency->code), // Zero average cost
        'quantity_on_hand' => 5.0, // Some stock available
    ]);

    $lineDto = new CreateStockMoveProductLineDTO(
        product_id: $productWithoutCost->id,
        quantity: 1.0,
        from_location_id: $this->stockLocation->id,
        to_location_id: $this->vendorLocation->id,
        description: 'Manual outgoing without cost',
        source_type: 'Test',
        source_id: 999,
    );

    $dto = new CreateStockMoveDTO(
        company_id: $this->company->id,
        move_type: StockMoveType::Outgoing,
        status: StockMoveStatus::Done,
        move_date: now(),
        created_by_user_id: $this->user->id,
        product_lines: [$lineDto],
        reference: 'SM-OUT-NO-COST',
        description: 'Manual outgoing stock without cost info',
        source_type: 'Test',
        source_id: 999,
    );

    // Act & Assert: Should throw RuntimeException for COGS calculation (outgoing moves still use old logic)
    expect(fn() => app(\Modules\Inventory\Actions\Inventory\CreateStockMoveAction::class)->execute($dto))
        ->toThrow(RuntimeException::class, 'Cannot calculate COGS for product');
});
