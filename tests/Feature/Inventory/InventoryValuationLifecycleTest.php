<?php

use Brick\Money\Money;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Inventory\Actions\LandedCost\PostLandedCostAction;
use Kezi\Inventory\Enums\Inventory\LandedCostStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Inventory\Models\InventoryCostLayer;
use Kezi\Inventory\Models\LandedCost;
use Kezi\Inventory\Models\LandedCostLine;
use Kezi\Inventory\Models\StockMove;
use Kezi\Product\Models\Product;

beforeEach(function () {
    // Setup basic requirements: Company, Currency, Test Product
    $this->company = \App\Models\Company::factory()->create();
    $this->user = \App\Models\User::factory()->create();
    $this->actingAs($this->user);

    // Setup Accounts (Inventory, COGS, Expense)
    $this->inventoryAccount = \Kezi\Accounting\Models\Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '101000',
        'name' => 'Inventory Asset',
        'type' => \Kezi\Accounting\Enums\Accounting\AccountType::CurrentAssets,
    ]);
    $this->cogsAccount = \Kezi\Accounting\Models\Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '501000',
        'name' => 'Cost of Goods Sold',
        'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Expense,
    ]);
    $this->expenseAccount = \Kezi\Accounting\Models\Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '601000',
        'name' => 'Freight Expense',
        'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Expense,
    ]);

    // Setup Journals
    $this->company->update([
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_expense_account_id' => $this->expenseAccount->id,
        'inventory_adjustment_account_id' => $this->expenseAccount->id, // Add this for safety
        'default_purchase_journal_id' => \Kezi\Accounting\Models\Journal::factory()->create(['company_id' => $this->company->id])->id,
        'default_sales_journal_id' => \Kezi\Accounting\Models\Journal::factory()->create(['company_id' => $this->company->id])->id,
    ]);

    // Setup Locations
    $this->sourceLocation = \Kezi\Inventory\Models\StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Inventory\Enums\Inventory\StockLocationType::Vendor,
    ]);
    $this->destLocation = \Kezi\Inventory\Models\StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Inventory\Enums\Inventory\StockLocationType::Internal,
    ]);
});

/**
 * Scenario: Buy 10 items. Sell 0 items. Add $50 Landed Cost.
 * Expectation:
 * - Inventory Asset increases by $50.
 * - Inventory Cost Layer unit cost increases by $5 ($50 / 10).
 */
test('landed cost updates inventory asset and fifo layer when items are in stock', function () {
    // 1. Create Product (FIFO)
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'storable',
        'inventory_valuation_method' => ValuationMethod::FIFO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->expenseAccount->id, // Simplified
        'default_cogs_account_id' => $this->cogsAccount->id,
    ]);

    // 2. Initial Purchase (Stock Move In) - Qty 10 @ $100 total ($10 each)
    $stockMove = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Done,
        'move_date' => now(),
    ]);
    // Create layers manually or trust the system? TDD implies trusting system or mocking
    // For this test, we need a Real Cost Layer to exist to verify it gets updated.
    // The service creates layers primarily in processIncomingStockFIFOLIFO.
    // Let's seed a layer directly to isolate the Landed Cost logic test.
    $originalUnitCost = Money::of(10, $this->company->currency->code);
    $layer = InventoryCostLayer::create([
        'product_id' => $product->id,
        'quantity' => 10,
        'remaining_quantity' => 10, // FULLY IN STOCK
        'cost_per_unit' => $originalUnitCost,
        'purchase_date' => now(),
        'source_type' => StockMove::class,
        'source_id' => $stockMove->id,
        'company_id' => $this->company->id,
    ]);

    // Link product line for query logic
    $productLine = new \Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO(
        $product->id, 10, $this->sourceLocation->id, $this->destLocation->id
    );
    $stockMove->productLines()->create([
        'product_id' => $product->id,
        'quantity' => 10,
        'company_id' => $this->company->id,
        'from_location_id' => $this->sourceLocation->id,
        'to_location_id' => $this->destLocation->id,
    ]);

    // 3. Create Landed Cost Record - $50 Additional
    $landedCost = LandedCost::create([
        'company_id' => $this->company->id,
        'status' => LandedCostStatus::Draft,
        'amount_total' => Money::of(50, $this->company->currency->code),
        'date' => now(),
        'allocation_method' => \Kezi\Inventory\Enums\Inventory\LandedCostAllocationMethod::ByQuantity,
    ]);

    $landedCostLine = LandedCostLine::create([
        'landed_cost_id' => $landedCost->id,
        'stock_move_id' => $stockMove->id,
        'additional_cost' => Money::of(50, $this->company->currency->code),
        'company_id' => $this->company->id,
    ]);

    // 4. Execute PostLandedCostAction
    // This should fail currently because the logic to update CostLayer is missing
    app(PostLandedCostAction::class)->execute($landedCost);

    // 5. Assertions
    $landedCost->refresh();
    expect($landedCost->status)->toBe(LandedCostStatus::Posted);

    // Assert Cost Layer Updated
    $layer->refresh();
    // Original $10 + ($50 / 10) = $15
    $expectedUnitCost = 15.0;
    expect($layer->cost_per_unit->getAmount()->toFloat())
        ->toBe($expectedUnitCost, 'Cost layer unit cost should increase to 15.0');

    // Assert Journal Entry
    $je = JournalEntry::find($landedCost->journal_entry_id);
    expect($je)->not->toBeNull();

    // Debit Inventory Asset 50.0
    $debitLine = $je->lines()->where('account_id', $this->inventoryAccount->id)->first();
    expect($debitLine)->not->toBeNull();
    expect($debitLine->debit->getAmount()->toFloat())->toBe(50.0);

    // No COGS hit
    $cogsLine = $je->lines()->where('account_id', $this->cogsAccount->id)->first();
    expect($cogsLine)->toBeNull('Should not hit COGS when items are in stock');

});

/**
 * Scenario: Buy 10 items. Sell 5 items. Add $50 Landed Cost.
 * Expectation:
 * - Inventory Asset increases by $25 (50% of 50).
 * - COGS increases by $25 (50% of 50).
 * - Inventory Cost Layer unit cost increases by $5 (still applies to remaining).
 */
test('landed cost splits between inventory and cogs when items are partially sold', function () {
    // 1. Create Product (FIFO)
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'storable',
        'inventory_valuation_method' => ValuationMethod::FIFO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->expenseAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
    ]);

    // 2. Initial Purchase Qty 10
    $stockMove = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Done,
    ]);

    $originalUnitCost = Money::of(10, $this->company->currency->code);
    $layer = InventoryCostLayer::create([
        'product_id' => $product->id,
        'quantity' => 10,
        'remaining_quantity' => 5, // 5 SOLD ALREADY
        'cost_per_unit' => $originalUnitCost,
        'purchase_date' => now(),
        'source_type' => StockMove::class,
        'source_id' => $stockMove->id,
        'company_id' => $this->company->id,
    ]);

    $stockMove->productLines()->create([
        'product_id' => $product->id,
        'quantity' => 10,
        'company_id' => $this->company->id,
        'from_location_id' => $this->sourceLocation->id,
        'to_location_id' => $this->destLocation->id,
    ]);

    // 3. Landed Cost $50
    $landedCost = LandedCost::create([
        'company_id' => $this->company->id,
        'status' => LandedCostStatus::Draft,
        'amount_total' => Money::of(50, $this->company->currency->code),
        'date' => now(),
        'allocation_method' => \Kezi\Inventory\Enums\Inventory\LandedCostAllocationMethod::ByQuantity,
    ]);

    LandedCostLine::create([
        'landed_cost_id' => $landedCost->id,
        'stock_move_id' => $stockMove->id,
        'additional_cost' => Money::of(50, $this->company->currency->code),
        'company_id' => $this->company->id,
    ]);

    // 4. Execute
    app(PostLandedCostAction::class)->execute($landedCost);

    // 5. Assertions
    $je = JournalEntry::find($landedCost->journal_entry_id);

    // Debit Inventory Asset (5 remaining / 10 total) * 50 = 25
    $invLine = $je->lines()->where('account_id', $this->inventoryAccount->id)->first();
    expect($invLine)->not->toBeNull();
    expect($invLine->debit->getAmount()->toFloat())->toBe(25.0);

    // Debit COGS (5 sold / 10 total) * 50 = 25
    $cogsLine = $je->lines()->where('account_id', $this->cogsAccount->id)->first();
    expect($cogsLine)->not->toBeNull();
    expect($cogsLine->debit->getAmount()->toFloat())->toBe(25.0);

    // Layer Update: The unit cost of the *remaining* stock should effectively increase.
    // The "standard" says the layer unit cost becomes Original + (Total Landed Cost / Total Qty)
    // = 10 + (50/10) = 15.
    // This allows future sales to carry the correct cost.
    $layer->refresh();
    expect($layer->cost_per_unit->getAmount()->toFloat())->toBe(15.0);
});
