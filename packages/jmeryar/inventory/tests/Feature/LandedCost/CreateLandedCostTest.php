<?php

use Jmeryar\Inventory\Actions\LandedCost\AllocateLandedCostsAction;
use Jmeryar\Inventory\Actions\LandedCost\CreateLandedCostAction;
use Jmeryar\Inventory\Actions\LandedCost\PostLandedCostAction;
use Jmeryar\Inventory\DataTransferObjects\LandedCost\LandedCostData;
use Jmeryar\Inventory\Enums\Inventory\LandedCostAllocationMethod;
use Jmeryar\Inventory\Models\LandedCost;
use Jmeryar\Inventory\Models\StockMoveValuation;

it('can create and post a landed cost', function () {
    // 1. Setup Data
    $company = \App\Models\Company::factory()->create();
    $currency = \Jmeryar\Foundation\Models\Currency::factory()->create(['code' => 'USD']);
    $company->currency_id = $currency->id;

    // Create test accounts for journal entries
    $inventoryAccount = \Jmeryar\Accounting\Models\Account::create([
        'company_id' => $company->id,
        'name' => 'Inventory Account',
        'code' => '1500',
        'type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::CurrentAssets,
    ]);

    $expenseAccount = \Jmeryar\Accounting\Models\Account::create([
        'company_id' => $company->id,
        'name' => 'Landed Cost Expense',
        'code' => '5200',
        'type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::Expense,
    ]);

    $company->inventory_adjustment_account_id = $expenseAccount->id;

    // Create default journal
    $journal = \Jmeryar\Accounting\Models\Journal::create([
        'company_id' => $company->id,
        'name' => 'Purchase Journal',
        'code' => 'PURCH',
        'short_code' => 'PURCH',
        'type' => \Jmeryar\Accounting\Enums\Accounting\JournalType::Purchase,
    ]);

    $company->default_purchase_journal_id = $journal->id;
    $company->save();

    $user = \App\Models\User::factory()->create();

    $product = \Jmeryar\Product\Models\Product::factory()->create([
        'company_id' => $company->id,
        'default_inventory_account_id' => $inventoryAccount->id,
    ]);

    // Create Stock Location
    $location = \Jmeryar\Inventory\Models\StockLocation::create([
        'company_id' => $company->id,
        'name' => 'Main Warehouse',
        'type' => \Jmeryar\Inventory\Enums\Inventory\StockLocationType::Internal,
    ]);

    // Create a Stock Picking
    $picking = \Jmeryar\Inventory\Models\StockPicking::create([
        'company_id' => $company->id,
        'type' => \Jmeryar\Inventory\Enums\Inventory\StockPickingType::Receipt,
        'state' => \Jmeryar\Inventory\Enums\Inventory\StockPickingState::Done,
        'scheduled_date' => now(),
        'created_by_user_id' => $user->id,
    ]);

    $move1 = \Jmeryar\Inventory\Models\StockMove::create([
        'company_id' => $company->id,
        'picking_id' => $picking->id,
        'move_type' => \Jmeryar\Inventory\Enums\Inventory\StockMoveType::InternalTransfer,
        'status' => \Jmeryar\Inventory\Enums\Inventory\StockMoveStatus::Done,
        'move_date' => now(),
        'created_by_user_id' => $user->id,
    ]);

    // Create product line for the move to have quantity
    \Jmeryar\Inventory\Models\StockMoveProductLine::create([
        'company_id' => $company->id,
        'stock_move_id' => $move1->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'from_location_id' => $location->id,
        'to_location_id' => $location->id,
    ]);

    // Create valuation for the move (initial cost)
    \Jmeryar\Inventory\Models\StockMoveValuation::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'stock_move_id' => $move1->id,
        'quantity' => 10,
        'cost_impact' => 1000, // 10.00 in base currency minor units
        'valuation_method' => \Jmeryar\Inventory\Enums\Inventory\ValuationMethod::STANDARD,
        'move_type' => \Jmeryar\Inventory\Enums\Inventory\StockMoveType::InternalTransfer,
        'cost_source' => \Jmeryar\Inventory\Enums\Inventory\CostSource::Manual,
        'source_type' => \Jmeryar\Inventory\Models\StockMove::class,
        'source_id' => $move1->id,
    ]);

    // 2. Create Landed Cost
    $dto = new LandedCostData(
        company: $company,
        date: now(),
        amount_total: \Brick\Money\Money::of(100, $currency->code), // 100.00 additional cost
        allocation_method: LandedCostAllocationMethod::ByQuantity,
        description: 'Test Landed Cost',
        created_by_user: $user,
    );

    $action = app(CreateLandedCostAction::class);
    $landedCost = $action->execute($dto);

    expect($landedCost)->toBeInstanceOf(LandedCost::class);
    expect($landedCost->amount_total->getAmount()->toFloat())->toBe(100.0);

    // 3. Attach Stock Picking to Landed Cost (NEW WORKFLOW)
    $landedCost->stockPickings()->attach($picking->id);

    expect($landedCost->stockPickings)->toHaveCount(1);

    // 4. Get stock moves from attached pickings (simulating the Post action workflow)
    $stockMoves = $landedCost->stockPickings()
        ->with('moves')
        ->get()
        ->pluck('moves')
        ->flatten();

    // 5. Allocate Costs
    $allocateAction = app(AllocateLandedCostsAction::class);
    $allocateAction->execute($landedCost, $stockMoves);

    $landedCost->refresh();
    expect($landedCost->lines)->toHaveCount(1);
    expect($landedCost->lines->first()->additional_cost->getAmount()->toFloat())->toBe(100.0);

    // 6. Post Landed Cost
    $postAction = app(PostLandedCostAction::class);
    $postAction->execute($landedCost);

    $landedCost->refresh();

    expect($landedCost->status)->toBe(\Jmeryar\Inventory\Enums\Inventory\LandedCostStatus::Posted);

    // Check StockMoveValuation created
    $valuation = StockMoveValuation::where('source_type', LandedCost::class)
        ->where('source_id', $landedCost->id)
        ->first();

    expect($valuation)->not->toBeNull();
    expect($valuation->cost_impact->getAmount()->toFloat())->toBe(100.0);

    // 7. Verify Journal Entry was created
    expect($landedCost->journal_entry_id)->not->toBeNull();

    $journalEntry = $landedCost->journalEntry;
    expect($journalEntry)->not->toBeNull();
    expect($journalEntry->reference)->toBe("LC-{$landedCost->id}");
    expect($journalEntry->state)->toBe(\Jmeryar\Accounting\Enums\Accounting\JournalEntryState::Posted);

    // Verify Journal Entry lines
    $journalEntryLines = $journalEntry->lines;
    expect($journalEntryLines)->toHaveCount(2); // 1 debit (inventory), 1 credit (expense)

    // Find debit line (Inventory Account)
    $debitLine = $journalEntryLines->firstWhere('account_id', $inventoryAccount->id);
    expect($debitLine)->not->toBeNull();
    expect($debitLine->debit->getAmount()->toFloat())->toBe(100.0);
    expect($debitLine->credit->getAmount()->toFloat())->toBe(0.0);

    // Find credit line (Expense Account)
    $creditLine = $journalEntryLines->firstWhere('account_id', $expenseAccount->id);
    expect($creditLine)->not->toBeNull();
    expect($creditLine->debit->getAmount()->toFloat())->toBe(0.0);
    expect($creditLine->credit->getAmount()->toFloat())->toBe(100.0);
});
