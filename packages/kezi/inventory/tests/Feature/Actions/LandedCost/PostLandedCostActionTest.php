<?php

namespace Kezi\Inventory\Tests\Feature\Actions\LandedCost;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Enums\Accounting\JournalEntryState;
use Kezi\Accounting\Models\Account;
use Kezi\Inventory\Actions\LandedCost\PostLandedCostAction;
use Kezi\Inventory\Enums\Inventory\LandedCostStatus;
use Kezi\Inventory\Models\LandedCost;
use Kezi\Inventory\Models\LandedCostLine;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockMoveValuation;
use Kezi\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();
    $this->action = app(PostLandedCostAction::class);

    // Setup accounts if not already set by trait
    $this->inventoryAccount = Account::firstOrCreate([
        'company_id' => $this->company->id,
        'code' => '1500',
    ], [
        'name' => 'Inventory',
        'type' => AccountType::CurrentAssets,
    ]);

    $this->expenseAccount = Account::firstOrCreate([
        'company_id' => $this->company->id,
        'code' => '5200',
    ], [
        'name' => 'Landed Cost Expense',
        'type' => AccountType::Expense,
    ]);

    $this->cogsAccount = Account::firstOrCreate([
        'company_id' => $this->company->id,
        'code' => '5000',
    ], [
        'name' => 'COGS',
        'type' => AccountType::Expense,
    ]);

    $this->company->update([
        'inventory_adjustment_account_id' => $this->expenseAccount->id,
        'default_expense_account_id' => $this->expenseAccount->id,
    ]);
});

it('posts a landed cost and creates journal entry', function () {
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
    ]);

    $landedCost = LandedCost::create([
        'company_id' => $this->company->id,
        'amount_total' => Money::of(100, $this->company->currency->code),
        'allocation_method' => \Kezi\Inventory\Enums\Inventory\LandedCostAllocationMethod::ByQuantity,
        'date' => now(),
        'status' => LandedCostStatus::Draft,
    ]);

    $move = StockMove::factory()->create(['company_id' => $this->company->id]);
    \Kezi\Inventory\Models\StockMoveProductLine::factory()->create([
        'stock_move_id' => $move->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    LandedCostLine::create([
        'company_id' => $this->company->id,
        'landed_cost_id' => $landedCost->id,
        'stock_move_id' => $move->id,
        'additional_cost' => Money::of(100, $this->company->currency->code),
    ]);

    $this->action->execute($landedCost);

    expect($landedCost->refresh()->status)->toBe(LandedCostStatus::Posted);
    expect($landedCost->journal_entry_id)->not->toBeNull();

    $journalEntry = $landedCost->journalEntry;
    expect($journalEntry->state)->toBe(JournalEntryState::Posted);

    // Check lines: Debit Inventory, Credit Expense
    expect($journalEntry->lines)->toHaveCount(2);

    $debitLine = $journalEntry->lines->firstWhere('account_id', $this->inventoryAccount->id);
    expect($debitLine->debit->isEqualTo(Money::of(100, $this->company->currency->code)))->toBeTrue();

    $creditLine = $journalEntry->lines->firstWhere('account_id', $this->expenseAccount->id);
    expect($creditLine->credit->isEqualTo(Money::of(100, $this->company->currency->code)))->toBeTrue();

    // Check StockMoveValuation
    $valuation = StockMoveValuation::where('source_type', LandedCost::class)
        ->where('source_id', $landedCost->id)
        ->first();

    expect($valuation)->not->toBeNull()
        ->and($valuation->cost_impact->isEqualTo(Money::of(100, $this->company->currency->code)))->toBeTrue();
});

it('does nothing if landed cost is already posted', function () {
    $landedCost = LandedCost::create([
        'company_id' => $this->company->id,
        'amount_total' => Money::of(100, $this->company->currency->code),
        'allocation_method' => \Kezi\Inventory\Enums\Inventory\LandedCostAllocationMethod::ByQuantity,
        'date' => now(),
        'status' => LandedCostStatus::Posted,
    ]);

    $this->action->execute($landedCost);

    // Should not change anything or throw error
    expect($landedCost->refresh()->status)->toBe(LandedCostStatus::Posted);
});
