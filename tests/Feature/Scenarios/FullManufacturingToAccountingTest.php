<?php

namespace Tests\Feature\Scenarios;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\JournalEntry;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Models\StockLocation;
use Modules\Inventory\Models\StockMove;
use Modules\Manufacturing\Actions\Accounting\CreateJournalEntryForManufacturingAction;
use Modules\Manufacturing\Actions\ConfirmManufacturingOrderAction;
use Modules\Manufacturing\Actions\ConsumeComponentsAction;
use Modules\Manufacturing\Actions\CreateBOMAction;
use Modules\Manufacturing\Actions\CreateManufacturingOrderAction;
use Modules\Manufacturing\Actions\ProduceFinishedGoodsAction;
use Modules\Manufacturing\Actions\StartProductionAction;
use Modules\Manufacturing\DataTransferObjects\CreateBOMDTO;
use Modules\Manufacturing\DataTransferObjects\BOMLineDTO;
use Modules\Manufacturing\DataTransferObjects\CreateManufacturingOrderDTO;
use Modules\Manufacturing\Enums\BOMType;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Models\ManufacturingOrder;
use Modules\Product\Enums\Products\ProductType;
use Modules\Product\Models\Product;
use Tests\TestCase;
use Tests\Traits\WithConfiguredCompany;

class FullManufacturingToAccountingTest extends TestCase
{
    use RefreshDatabase;
    use WithConfiguredCompany;

    protected User $user;
    protected Account $rmAccount;
    protected Account $fgAccount;
    protected Account $wipAccount;
    protected Account $productionCostAccount;
    protected Journal $stockJournal;
    protected Journal $manufacturingJournal;
    protected StockLocation $stockLocation;
    protected StockLocation $productionLocation;
    protected Product $rawMaterial1;
    protected Product $rawMaterial2;
    protected Product $finishedGood;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupWithConfiguredCompany();

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company);

        // 1. Setup Accounts
        $this->rmAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '1010',
            'name' => 'Raw Materials',
            'type' => \Modules\Accounting\Enums\Accounting\AccountType::CurrentAssets,
        ]);

        $this->fgAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '1020',
            'name' => 'Finished Goods',
            'type' => \Modules\Accounting\Enums\Accounting\AccountType::CurrentAssets,
        ]);

        $this->wipAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '1030',
            'name' => 'Work in Progress',
            'type' => \Modules\Accounting\Enums\Accounting\AccountType::CurrentAssets,
        ]);

        // 2. Setup Journals
        $this->stockJournal = Journal::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Stock Journal',
            'short_code' => 'STJ',
            'type' => \Modules\Accounting\Enums\Accounting\JournalType::Miscellaneous,
        ]);

        $this->manufacturingJournal = Journal::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Manufacturing Operations',
            'short_code' => 'MFG',
            'type' => \Modules\Accounting\Enums\Accounting\JournalType::Miscellaneous,
        ]);

        // Configure Company Defaults
        $this->company->update([
            'default_manufacturing_journal_id' => $this->manufacturingJournal->id,
            'default_raw_materials_inventory_id' => $this->rmAccount->id,
            'default_finished_goods_inventory_id' => $this->fgAccount->id,
        ]);

        // 3. Setup Locations
        $this->stockLocation = StockLocation::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'WH/Stock',
            'type' => \Modules\Inventory\Enums\Inventory\StockLocationType::Internal,
        ]);

        $this->productionLocation = StockLocation::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Virtual/Production',
            'type' => \Modules\Inventory\Enums\Inventory\StockLocationType::Internal, // Using Internal as Production type is missing in Enum
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
    }

    public function test_full_manufacturing_flow_to_accounting()
    {
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
            isActive: true,
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
            ]
        );

        $bom = app(CreateBOMAction::class)->execute($bomDto);

        $this->assertNotNull($bom);
        $this->assertCount(2, $bom->lines);

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

        $this->assertInstanceOf(ManufacturingOrder::class, $mo);
        $this->assertEquals(ManufacturingOrderStatus::Draft, $mo->status);
        $this->assertCount(2, $mo->lines); // Should match BOM lines

        // ==========================================
        // 3. Confirm MO
        // ==========================================

        app(ConfirmManufacturingOrderAction::class)->execute($mo);
        $this->assertEquals(ManufacturingOrderStatus::Confirmed, $mo->refresh()->status);

        // Verify WorkOrder creation (if any)
        // With single-operation BOM, it might create one WO
        if ($mo->workOrders()->exists()) {
             $this->assertEquals('pending', $mo->workOrders->first()->status);
        }

        // ==========================================
        // 4. Start Production
        // ==========================================

        app(StartProductionAction::class)->execute($mo);
        $this->assertEquals(ManufacturingOrderStatus::InProgress, $mo->refresh()->status);

        if ($mo->workOrders()->exists()) {
             $this->assertEquals('ready', $mo->workOrders->first()->status);
        }

        // ==========================================
        // 5. Consume Components
        // ==========================================
        // This should create stock moves for components

        // Ensure we have 'reservation' or just consume?
        // ConsumeComponentsAction consumes what is required.

        app(ConsumeComponentsAction::class)->execute($mo);

        // Verify MO Lines updated
        foreach ($mo->refresh()->lines as $line) {
            $this->assertEquals($line->quantity_required, $line->quantity_consumed);
            // Verify Stock Move ID is set
            $this->assertNotNull($line->stock_move_id);

            $move = StockMove::find($line->stock_move_id);
            $this->assertEquals(StockMoveStatus::Done, $move->status);
            $this->assertEquals($this->stockLocation->id, $move->productLines->first()->from_location_id);
            // Based on analysis, current impl might use destination from MO, which we set to Stock.
            // This is "Internal Transfer" logic in current code.
            // Assert that logic holds, even if it's conceptually weird for consumption (Stock -> Stock).
            // Or maybe it should be Stock -> Production?
            // Ideally we check if a move was created.
        }

        // ==========================================
        // 6. Produce Finished Goods
        // ==========================================

        // Mocking StockMoveService in unit tests is fine, but here we want real execution.
        // ProduceFinishedGoodsAction uses StockMoveService::createMove.
        // We need to ensure that works.

        app(ProduceFinishedGoodsAction::class)->execute($mo);

        $this->assertEquals(ManufacturingOrderStatus::Done, $mo->refresh()->status);
        $this->assertEquals(10, $mo->quantity_produced);

        // Verify FG Stock Move
        // We can search for stock move with source_type ManufacturingOrder and Incoming type
        $fgMove = StockMove::where('source_type', ManufacturingOrder::class)
            ->where('source_id', $mo->id)
            ->where('move_type', \Modules\Inventory\Enums\Inventory\StockMoveType::Incoming)
            ->first();

        $this->assertNotNull($fgMove);
        $this->assertEquals(StockMoveStatus::Done, $fgMove->status);
        // Quantity should be 10
        $this->assertEquals(10, $fgMove->productLines->sum('quantity'));

        // ==========================================
        // 7. Verify Accounting (Journal Entry)
        // ==========================================

        // Check if JE was auto-created.
        // Based on `ManufacturingAccountingIntegrationTest`, there is a manual action `CreateJournalEntryForManufacturingAction`.
        // Let's see if MO has journal_entry_id set (it shouldn't if auto-creation is missing).

        if (! $mo->journal_entry_id) {
            // Trigger manual accounting action as per integration pattern
            $je = app(CreateJournalEntryForManufacturingAction::class)->execute($mo, $this->user);
            $mo->refresh();
            $this->assertEquals($je->id, $mo->journal_entry_id);
        }

        $je = $mo->journalEntry;
        $this->assertNotNull($je);
        $this->assertEquals($this->manufacturingJournal->id, $je->journal_id);

        // Verify Lines
        // Credit Raw Materials
        // Debit Finished Goods

        // Total Cost:
        // RM1: 2 units * 10 * 100 = 2000
        // RM2: 1 unit * 10 * 50 = 500
        // Total = 2500

        // Brick\Money stores in minor units. Assuming currency is default (e.g. USD or IQD).
        // If IQD (3 decimals), 2500 IQD = 2,500,000 minor units.
        // If USD (2 decimals), 2500 USD = 250,000 minor units.
        // Our factory created currency. Let's check amount matches expectation.

        $expectedAmount = 2500;
        // Adjust for currency scale if needed, but comparing float from Money is easier if helpers allow.

        $debitLine = $je->lines()->where('account_id', $this->fgAccount->id)->where('debit', '>', 0)->first();
        $this->assertNotNull($debitLine, 'Finished Goods Debit Line missing');
        $this->assertEquals($expectedAmount, $debitLine->debit->getAmount()->toFloat());

        $totalCredit = 0.0;
        foreach ($je->lines as $line) {
            if ($line->account_id === $this->rmAccount->id && $line->credit->isPositive()) {
                $totalCredit += $line->credit->getAmount()->toFloat();
            }
        }
        $this->assertEquals($expectedAmount, $totalCredit, 'Total Credit to Raw Materials mismatch');
    }
}
