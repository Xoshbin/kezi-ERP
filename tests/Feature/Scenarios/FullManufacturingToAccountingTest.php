<?php

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\Journal;
use Jmeryar\Accounting\Models\JournalEntry;
use Jmeryar\Inventory\Enums\Inventory\StockLocationType;
use Jmeryar\Inventory\Enums\Inventory\StockMoveStatus;
use Jmeryar\Inventory\Enums\Inventory\StockMoveType;
use Jmeryar\Inventory\Models\StockLocation;
use Jmeryar\Inventory\Models\StockMove;
use Jmeryar\Manufacturing\Actions\Accounting\CreateJournalEntryForManufacturingAction;
use Jmeryar\Manufacturing\Actions\ConfirmManufacturingOrderAction;
use Jmeryar\Manufacturing\Actions\ConsumeComponentsAction;
use Jmeryar\Manufacturing\Actions\CreateBOMAction;
use Jmeryar\Manufacturing\Actions\CreateManufacturingOrderAction;
use Jmeryar\Manufacturing\Actions\ProduceFinishedGoodsAction;
use Jmeryar\Manufacturing\Actions\StartProductionAction;
use Jmeryar\Manufacturing\DataTransferObjects\BOMLineDTO;
use Jmeryar\Manufacturing\DataTransferObjects\CreateBOMDTO;
use Jmeryar\Manufacturing\DataTransferObjects\CreateManufacturingOrderDTO;
use Jmeryar\Manufacturing\Enums\BOMType;
use Jmeryar\Manufacturing\Enums\ManufacturingOrderStatus;
use Jmeryar\Manufacturing\Models\ManufacturingOrder;
use Jmeryar\Product\Enums\Products\ProductType;
use Jmeryar\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);

    // 1. Setup Accounts
    $this->rmAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1010',
        'name' => 'Raw Materials',
        'type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::CurrentAssets,
    ]);

    $this->fgAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1020',
        'name' => 'Finished Goods',
        'type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::CurrentAssets,
    ]);

    $this->wipAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1030',
        'name' => 'Work in Progress',
        'type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::CurrentAssets,
    ]);

    // 2. Setup Journals
    $this->stockJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Stock Journal',
        'short_code' => 'STJ',
        'type' => \Jmeryar\Accounting\Enums\Accounting\JournalType::Miscellaneous,
    ]);

    $this->manufacturingJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Manufacturing Operations',
        'short_code' => 'MFG',
        'type' => \Jmeryar\Accounting\Enums\Accounting\JournalType::Miscellaneous,
    ]);

    // Configure Company Defaults
    $this->company->update([
        'default_manufacturing_journal_id' => $this->manufacturingJournal->id,
        'default_raw_materials_inventory_id' => $this->rmAccount->id,
        'default_finished_goods_inventory_id' => $this->fgAccount->id,
        'default_wip_account_id' => $this->wipAccount->id,
    ]);

    // 3. Setup Locations
    $this->stockLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'WH/Stock',
        'type' => StockLocationType::Internal,
    ]);

    $this->productionLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Virtual/Production',
        // Using Internal as Production type is missing in Enum in this version
        'type' => StockLocationType::Internal,
    ]);

    // 4. Setup Products
    $this->rawMaterial1 = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Raw Material 1',
        'type' => ProductType::Storable,
        'unit_price' => Money::of(100, $this->company->currency->code),
        'default_inventory_account_id' => $this->rmAccount->id,
    ]);

    $this->rawMaterial2 = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Raw Material 2',
        'type' => ProductType::Storable,
        'unit_price' => Money::of(50, $this->company->currency->code),
        'default_inventory_account_id' => $this->rmAccount->id,
    ]);

    $this->finishedGood = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Finished Good',
        'type' => ProductType::Storable,
        'default_inventory_account_id' => $this->fgAccount->id,
    ]);
});

it('completes full manufacturing flow to accounting', function () {
    Carbon::setTestNow('2026-03-01 10:00:00');

    // ==========================================
    // 1. Create BOM
    // ==========================================
    // FG = 2 * RM1 + 1 * RM2
    // Cost = 2*100 + 1*50 = 250

    $bomDto = new CreateBOMDTO(
        companyId: $this->company->id,
        productId: $this->finishedGood->id,
        code: 'BOM-FG-001',
        name: ['en' => 'BOM for Finished Good'],
        type: BOMType::Normal,
        quantity: 1,
        lines: [
            new BOMLineDTO(
                productId: $this->rawMaterial1->id,
                quantity: 2,
                unitCost: Money::of(100, $this->company->currency->code)
            ),
            new BOMLineDTO(
                productId: $this->rawMaterial2->id,
                quantity: 1,
                unitCost: Money::of(50, $this->company->currency->code)
            ),
        ],
        isActive: true
    );

    $bom = app(CreateBOMAction::class)->execute($bomDto);

    expect($bom)->not->toBeNull()
        ->and($bom->lines)->toHaveCount(2);

    // ==========================================
    // 2. Create Manufacturing Order
    // ==========================================

    $moDto = new CreateManufacturingOrderDTO(
        companyId: $this->company->id,
        bomId: $bom->id,
        productId: $this->finishedGood->id,
        quantityToProduce: 10, // Produce 10 units
        plannedStartDate: now(),
        sourceLocationId: $this->stockLocation->id, // Take components from Stock
        destinationLocationId: $this->stockLocation->id, // Put FG into Stock
    );

    $mo = app(CreateManufacturingOrderAction::class)->execute($moDto);

    expect($mo)->toBeInstanceOf(ManufacturingOrder::class)
        ->and($mo->status)->toBe(ManufacturingOrderStatus::Draft)
        ->and($mo->lines)->toHaveCount(2);

    // ==========================================
    // 3. Confirm MO
    // ==========================================

    app(ConfirmManufacturingOrderAction::class)->execute($mo);
    expect($mo->refresh()->status)->toBe(ManufacturingOrderStatus::Confirmed);

    // Verify WorkOrder creation (if any)
    if ($mo->workOrders()->exists()) {
        expect($mo->workOrders->first()->status)->toBe('pending');
    }

    // ==========================================
    // 4. Start Production
    // ==========================================

    app(StartProductionAction::class)->execute($mo);
    expect($mo->refresh()->status)->toBe(ManufacturingOrderStatus::InProgress);

    if ($mo->workOrders()->exists()) {
        expect($mo->workOrders->first()->status)->toBe('ready');
    }

    // ==========================================
    // 5. Consume Components
    // ==========================================

    app(ConsumeComponentsAction::class)->execute($mo);

    // Verify MO Lines updated
    foreach ($mo->refresh()->lines as $line) {
        expect($line->quantity_required)->toEqual($line->quantity_consumed);
        // Verify Stock Move ID is set
        expect($line->stock_move_id)->not->toBeNull();

        $move = StockMove::find($line->stock_move_id);
        expect($move->status)->toBe(StockMoveStatus::Done);
        expect($move->productLines->first()->from_location_id)->toBe($this->stockLocation->id);
    }

    // ==========================================
    // 6. Produce Finished Goods
    // ==========================================

    app(ProduceFinishedGoodsAction::class)->execute($mo);

    expect($mo->refresh()->status)->toBe(ManufacturingOrderStatus::Done)
        ->and((float) $mo->quantity_produced)->toBe(10.0);

    // Verify FG Stock Move
    $fgMove = StockMove::where('source_type', ManufacturingOrder::class)
        ->where('source_id', $mo->id)
        ->where('move_type', StockMoveType::Incoming)
        ->first();

    expect($fgMove)->not->toBeNull()
        ->and($fgMove->status)->toBe(StockMoveStatus::Done)
        ->and($fgMove->productLines->sum('quantity'))->toBe(10.0);

    // ==========================================
    // 7. Verify Accounting (Journal Entry)
    // ==========================================

    if (! $mo->journal_entry_id) {
        // Trigger manual accounting action as per integration pattern
        $je = app(CreateJournalEntryForManufacturingAction::class)->execute($mo, $this->user);
        $mo->refresh();
        expect($mo->journal_entry_id)->toBe($je->id);
    }

    $je = $mo->journalEntry;
    expect($je)->not->toBeNull()
        ->and($je->journal_id)->toBe($this->manufacturingJournal->id);

    // Total Cost:
    // RM1: 2 units * 10 * 100 = 2000
    // RM2: 1 unit * 10 * 50 = 500
    // Total = 2500

    $expectedAmount = 2500.0;

    $debitLine = $je->lines()->where('account_id', $this->fgAccount->id)->where('debit', '>', 0)->first();
    expect($debitLine)->not->toBeNull('Finished Goods Debit Line missing');
    expect($debitLine->debit->getAmount()->toFloat())->toBe($expectedAmount);

    $totalCredit = 0.0;
    foreach ($je->lines as $line) {
        if ($line->account_id === $this->wipAccount->id && $line->credit->isPositive()) {
            $totalCredit += $line->credit->getAmount()->toFloat();
        }
    }
    expect($totalCredit)->toBe($expectedAmount, 'Total Credit to WIP mismatch');
});
