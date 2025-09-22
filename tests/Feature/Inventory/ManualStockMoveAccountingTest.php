<?php

use App\Actions\Inventory\CreateStockMoveAction;
use App\DataTransferObjects\Inventory\CreateStockMoveDTO;
use App\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Inventory\ValuationMethod;
use App\Enums\Products\ProductType;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\StockMove;
use App\Models\StockMoveValuation;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        'type' => ProductType::Storable,
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
    $move = app(CreateStockMoveAction::class)->execute($dto);
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

