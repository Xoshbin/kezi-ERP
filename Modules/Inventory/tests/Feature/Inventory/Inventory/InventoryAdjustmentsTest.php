<?php

namespace Modules\Inventory\Tests\Feature\Inventory;

use App\Actions\Inventory\CreateInventoryAdjustmentAction;
use App\DataTransferObjects\Inventory\CreateInventoryAdjustmentDTO;
use App\DataTransferObjects\Inventory\InventoryAdjustmentLineDTO;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Inventory\StockPickingState;
use App\Enums\Inventory\StockPickingType;
use App\Models\JournalEntry;
use App\Models\Lot;
use App\Models\Product;
use App\Models\StockMove;
use App\Models\StockMoveLine;
use App\Models\StockMoveValuation;
use App\Models\StockPicking;
use App\Models\StockQuant;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    // Create COGS account
    $this->cogsAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create([
        'type' => 'cost_of_revenue',
        'name' => 'Cost of Goods Sold',
    ]);

    $this->product = \Modules\Product\Models\Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => \App\Enums\Inventory\ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'average_cost' => Money::of(150, $this->company->currency->code),
    ]);

    // Create inventory adjustment account
    $this->adjustmentAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create([
        'type' => 'expense',
        'name' => 'Inventory Adjustment',
    ]);

    $this->adjustmentAction = app(CreateInventoryAdjustmentAction::class);
});

it('creates positive adjustment with proper stock moves and journal entries', function () {
    $adjustmentDate = Carbon::create(2025, 2, 15);
    Carbon::setTestNow($adjustmentDate);

    // Create initial stock
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'quantity' => 10.0,
        'reserved_quantity' => 0.0,
    ]);

    // Create adjustment DTO for adding 5 units
    $adjustmentDto = new CreateInventoryAdjustmentDTO(
        company_id: $this->company->id,
        adjustment_date: $adjustmentDate,
        reference: 'ADJ-001',
        reason: 'Physical count adjustment',
        lines: [
            new InventoryAdjustmentLineDTO(
                product_id: $this->product->id,
                location_id: $this->stockLocation->id,
                counted_quantity: 15.0, // Current 10 + adjustment 5
                current_quantity: 10.0,
                lot_id: null,
            ),
        ],
        created_by_user_id: $this->user->id,
    );

    $adjustment = $this->adjustmentAction->execute($adjustmentDto);

    // Assert adjustment picking was created
    $picking = StockPicking::where('company_id', $this->company->id)
        ->where('type', StockPickingType::Internal)
        ->where('origin', 'ADJ-001')
        ->first();

    expect($picking)->not->toBeNull();
    expect($picking->state)->toBe(StockPickingState::Done);

    // Assert stock move was created
    $move = $picking->stockMoves()->first();
    expect($move)->not->toBeNull();
    expect($move->move_type)->toBe(StockMoveType::Adjustment);

    // Check product line
    $productLine = $move->productLines()->first();
    expect($productLine)->not->toBeNull();
    expect((float) $productLine->quantity)->toBe(5.0);
    expect($productLine->from_location_id)->toBe($this->company->adjustmentLocation->id);
    expect($productLine->to_location_id)->toBe($this->stockLocation->id);

    // Assert quant was updated
    $quant = StockQuant::where('company_id', $this->company->id)
        ->where('product_id', $this->product->id)
        ->where('location_id', $this->stockLocation->id)
        ->first();

    expect($quant->quantity)->toBe(15.0);

    // Assert journal entry was created
    $valuation = StockMoveValuation::where('stock_move_id', $move->id)->first();
    expect($valuation)->not->toBeNull();

    $journalEntry = $valuation->journalEntry;
    expect($journalEntry)->not->toBeNull();
    expect($journalEntry->state)->toBe(\App\Enums\Accounting\JournalEntryState::Posted);

    // Assert journal entry lines
    $lines = $journalEntry->lines;
    expect($lines->count())->toBe(2);

    // Find debit and credit lines by checking Money objects
    $debitLine = $lines->first(function ($line) {
        return $line->debit && $line->debit->isPositive();
    });
    $creditLine = $lines->first(function ($line) {
        return $line->credit && $line->credit->isPositive();
    });

    expect($debitLine)->not->toBeNull('No debit line found');
    expect($creditLine)->not->toBeNull('No credit line found');

    expect($debitLine->account_id)->toBe($this->inventoryAccount->id);
    expect($debitLine->debit->getAmount()->toFloat())->toBe(750.0); // 5 units * 150 cost
    expect($creditLine->account_id)->toBe($this->adjustmentAccount->id);
    expect($creditLine->credit->getAmount()->toFloat())->toBe(750.0);
});

it('creates negative adjustment with proper accounting', function () {
    $adjustmentDate = Carbon::create(2025, 2, 15);
    Carbon::setTestNow($adjustmentDate);

    // Create initial stock with cost layers
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'quantity' => 10.0,
        'reserved_quantity' => 0.0,
    ]);

    // Create cost layer to establish cost basis
    \App\Models\InventoryCostLayer::create([
        'product_id' => $this->product->id,
        'quantity' => 10.0,
        'cost_per_unit' => Money::of(150, $this->company->currency->code),
        'remaining_quantity' => 10.0,
        'purchase_date' => $adjustmentDate->subDay(),
        'source_type' => 'Test',
        'source_id' => 1,
    ]);

    // Create adjustment DTO for removing 3 units
    $adjustmentDto = new CreateInventoryAdjustmentDTO(
        company_id: $this->company->id,
        adjustment_date: $adjustmentDate,
        reference: 'ADJ-002',
        reason: 'Damaged goods write-off',
        lines: [
            new InventoryAdjustmentLineDTO(
                product_id: $this->product->id,
                location_id: $this->stockLocation->id,
                counted_quantity: 7.0, // Current 10 - adjustment 3
                current_quantity: 10.0,
                lot_id: null,
            ),
        ],
        created_by_user_id: $this->user->id,
    );

    $adjustment = $this->adjustmentAction->execute($adjustmentDto);

    // Assert stock move for negative adjustment
    $picking = StockPicking::where('origin', 'ADJ-002')->first();
    $move = $picking->stockMoves()->first();

    // Check product line
    $productLine = $move->productLines()->first();
    expect($productLine)->not->toBeNull();
    expect((float) $productLine->quantity)->toBe(3.0);
    expect($productLine->from_location_id)->toBe($this->stockLocation->id);
    expect($productLine->to_location_id)->toBe($this->company->adjustmentLocation->id);

    // Assert quant was reduced
    $quant = StockQuant::where('company_id', $this->company->id)
        ->where('product_id', $this->product->id)
        ->where('location_id', $this->stockLocation->id)
        ->first();

    expect($quant->quantity)->toBe(7.0);

    // Assert journal entry for negative adjustment
    $valuation = StockMoveValuation::where('stock_move_id', $move->id)->first();
    $journalEntry = $valuation->journalEntry;
    $lines = $journalEntry->lines;

    // Check if journal entry was created
    expect($journalEntry)->not->toBeNull('No journal entry created');
    expect($lines->count())->toBeGreaterThan(0, 'No journal entry lines created');

    // Find debit and credit lines by checking Money objects
    $debitLine = $lines->first(function ($line) {
        return $line->debit && $line->debit->isPositive();
    });
    $creditLine = $lines->first(function ($line) {
        return $line->credit && $line->credit->isPositive();
    });

    expect($debitLine)->not->toBeNull('No debit line found');
    expect($creditLine)->not->toBeNull('No credit line found');

    expect($debitLine->account_id)->toBe($this->adjustmentAccount->id);
    expect($debitLine->debit->getAmount()->toFloat())->toBe(450.0); // 3 units * 150 cost
    expect($creditLine->account_id)->toBe($this->inventoryAccount->id);
    expect($creditLine->credit->getAmount()->toFloat())->toBe(450.0);
});

it('handles lot-specific adjustments correctly', function () {
    $adjustmentDate = Carbon::create(2025, 2, 15);
    Carbon::setTestNow($adjustmentDate);

    // Create lots
    $lot1 = Lot::factory()->for($this->company)->create([
        'product_id' => $this->product->id,
        'lot_code' => 'LOT-001',
        'expiration_date' => Carbon::create(2025, 8, 15),
    ]);

    $lot2 = Lot::factory()->for($this->company)->create([
        'product_id' => $this->product->id,
        'lot_code' => 'LOT-002',
        'expiration_date' => Carbon::create(2025, 9, 15),
    ]);

    // Create initial quants for both lots
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'lot_id' => $lot1->id,
        'quantity' => 8.0,
        'reserved_quantity' => 0.0,
    ]);

    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'lot_id' => $lot2->id,
        'quantity' => 12.0,
        'reserved_quantity' => 0.0,
    ]);

    // Adjust only lot1 (reduce by 2 units)
    $adjustmentDto = new CreateInventoryAdjustmentDTO(
        company_id: $this->company->id,
        adjustment_date: $adjustmentDate,
        reference: 'ADJ-LOT-001',
        reason: 'Lot-specific adjustment',
        lines: [
            new InventoryAdjustmentLineDTO(
                product_id: $this->product->id,
                location_id: $this->stockLocation->id,
                counted_quantity: 6.0, // Current 8 - adjustment 2
                current_quantity: 8.0,
                lot_id: $lot1->id,
            ),
        ],
        created_by_user_id: $this->user->id,
    );

    $adjustment = $this->adjustmentAction->execute($adjustmentDto);

    // Assert stock move line was created for specific lot
    $picking = StockPicking::where('origin', 'ADJ-LOT-001')->first();
    $move = $picking->stockMoves()->first();

    $productLine = $move->productLines()->first();
    $moveLine = StockMoveLine::where('stock_move_product_line_id', $productLine->id)
        ->where('lot_id', $lot1->id)
        ->first();

    expect($moveLine)->not->toBeNull();
    expect($moveLine->quantity)->toBe(2.0);

    // Assert only lot1 quant was affected
    $quant1 = StockQuant::where('lot_id', $lot1->id)->first();
    expect($quant1->quantity)->toBe(6.0);

    $quant2 = StockQuant::where('lot_id', $lot2->id)->first();
    expect($quant2->quantity)->toBe(12.0); // Unchanged
});

it('prevents negative quantity adjustments', function () {
    // Create initial stock
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'quantity' => 5.0,
        'reserved_quantity' => 0.0,
    ]);

    // Try to adjust to negative quantity
    $adjustmentDto = new CreateInventoryAdjustmentDTO(
        company_id: $this->company->id,
        adjustment_date: Carbon::now(),
        reference: 'ADJ-INVALID',
        reason: 'Invalid adjustment',
        lines: [
            new InventoryAdjustmentLineDTO(
                product_id: $this->product->id,
                location_id: $this->stockLocation->id,
                counted_quantity: -2.0, // Invalid negative count
                current_quantity: 5.0,
                lot_id: null,
            ),
        ],
        created_by_user_id: $this->user->id,
    );

    expect(fn() => $this->adjustmentAction->execute($adjustmentDto))
        ->toThrow(\InvalidArgumentException::class, 'Counted quantity cannot be negative');
});

it('handles zero adjustments gracefully', function () {
    // Create initial stock
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'quantity' => 10.0,
        'reserved_quantity' => 0.0,
    ]);

    // Create adjustment with no change
    $adjustmentDto = new CreateInventoryAdjustmentDTO(
        company_id: $this->company->id,
        adjustment_date: Carbon::now(),
        reference: 'ADJ-ZERO',
        reason: 'No change needed',
        lines: [
            new InventoryAdjustmentLineDTO(
                product_id: $this->product->id,
                location_id: $this->stockLocation->id,
                counted_quantity: 10.0, // Same as current
                current_quantity: 10.0,
                lot_id: null,
            ),
        ],
        created_by_user_id: $this->user->id,
    );

    $adjustment = $this->adjustmentAction->execute($adjustmentDto);

    // Should not create any stock moves or journal entries
    $pickings = StockPicking::where('origin', 'ADJ-ZERO')->get();
    expect($pickings->count())->toBe(0);

    $journalEntries = JournalEntry::where('reference', 'ADJ-ZERO')->get();
    expect($journalEntries->count())->toBe(0);

    // Quant should remain unchanged
    $quant = StockQuant::where('company_id', $this->company->id)
        ->where('product_id', $this->product->id)
        ->where('location_id', $this->stockLocation->id)
        ->first();

    expect($quant->quantity)->toBe(10.0);
});
