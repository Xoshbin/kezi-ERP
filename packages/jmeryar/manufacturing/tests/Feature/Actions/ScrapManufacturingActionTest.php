<?php

namespace Jmeryar\Manufacturing\tests\Feature\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Enums\Accounting\AccountType;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Inventory\Enums\Inventory\StockLocationType;
use Jmeryar\Inventory\Enums\Inventory\StockMoveStatus;
use Jmeryar\Inventory\Enums\Inventory\StockMoveType;
use Jmeryar\Inventory\Models\StockLocation;
use Jmeryar\Inventory\Models\StockMove;
use Jmeryar\Manufacturing\Actions\ScrapManufacturingAction;
use Jmeryar\Manufacturing\Models\ManufacturingOrder;
use Jmeryar\Product\Models\Product;
use Tests\TestCase;
use Tests\Traits\WithConfiguredCompany;

class ScrapManufacturingActionTest extends TestCase
{
    use RefreshDatabase, WithConfiguredCompany;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupWithConfiguredCompany();
        $this->setupInventoryTestEnvironment();
    }

    public function test_it_can_scrap_components_and_generate_accounting_entries()
    {
        // 1. Setup Scrap Account & Location
        $scrapAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '600000',
            'name' => 'Scrap Expense',
            'type' => AccountType::Expense,
        ]);

        $this->company->update(['default_scrap_account_id' => $scrapAccount->id]);

        $scrapLocation = StockLocation::factory()->create([
            'company_id' => $this->company->id,
            'type' => StockLocationType::Scrap,
            'name' => 'Virtual Scrap',
        ]);

        // 2. Setup Product (Raw Material)
        $component = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Raw Material A',
            'type' => 'storable',
            'average_cost' => 100, // Cost per unit
        ]);

        // Ensure component has inventory accounts setup (via WithConfiguredCompany or Factory defaults)
        // Check WithConfiguredCompany trait if it sets defaults?
        // Assuming default accounts are set on company or product factory handles it.
        // Let's force set them to be sure for this test.
        $inventoryAccount = Account::factory()->create(['company_id' => $this->company->id, 'type' => AccountType::CurrentAssets, 'name' => 'Inventory']);
        $cogsAccount = Account::factory()->create(['company_id' => $this->company->id, 'type' => AccountType::Expense, 'name' => 'COGS']);

        $component->update([
            'default_inventory_account_id' => $inventoryAccount->id,
            'default_cogs_account_id' => $cogsAccount->id,
        ]);

        // 3. Create Manufacturing Order
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'source_location_id' => $this->company->default_stock_location_id,
        ]);

        // 4. Initial Stock (to allow move)
        // 4. Initial Stock (to allow move)
        $incomingMove = StockMove::create([
            'company_id' => $this->company->id,
            'product_id' => $component->id, // Add product_id to move for legacy support if needed
            'source_location_id' => $this->company->default_vendor_location_id,
            'destination_location_id' => $this->company->default_stock_location_id,
            'move_type' => StockMoveType::Incoming,
            'status' => StockMoveStatus::Draft,
            'move_date' => now(),
            'reference' => 'TEST-IN-001',
            'created_by_user_id' => $this->user->id,
        ]);
        $productLine = \Jmeryar\Inventory\Models\StockMoveProductLine::create([
            'company_id' => $this->company->id,
            'stock_move_id' => $incomingMove->id,
            'product_id' => $component->id,
            'quantity' => 10,
            'from_location_id' => $this->company->default_vendor_location_id,
            'to_location_id' => $this->company->default_stock_location_id,
        ]);

        \Jmeryar\Inventory\Models\StockMoveLine::create([
            'company_id' => $this->company->id,
            'stock_move_product_line_id' => $productLine->id,
            'quantity' => 10,
        ]);
        // Trigger incoming processing
        $incomingMove->update(['status' => StockMoveStatus::Done]);

        // 5. Execute Scrap Action
        $action = app(ScrapManufacturingAction::class);
        $action->execute($mo, [
            ['product_id' => $component->id, 'quantity' => 2],
        ]);

        // 6. Assertions
        $this->assertDatabaseHas('stock_moves', [
            'company_id' => $this->company->id,
            'move_type' => StockMoveType::Outgoing,
            'status' => StockMoveStatus::Done,
            'source_type' => ManufacturingOrder::class,
            'source_id' => $mo->id,
        ]);

        $scrapMove = StockMove::where('source_type', ManufacturingOrder::class)->latest()->first();
        $this->assertNotNull($scrapMove);

        $this->assertDatabaseHas('stock_move_product_lines', [
            'stock_move_id' => $scrapMove->id,
            'to_location_id' => $scrapLocation->id,
        ]);

        // Assert Journal Entry
        // The observer should have created a consolidated journal entry (or individual if not consolidated? Our service consolidates manual moves)
        // Wait, `ProcessOutgoingStockAction` calls `createConsolidatedManualStockMoveJournalEntry`.

        $journalEntry = \Jmeryar\Accounting\Models\JournalEntry::where('source_type', StockMove::class)
            ->where('source_id', $scrapMove->id)
            ->first();

        // Check StockMoveValuation
        $valuation = \Jmeryar\Inventory\Models\StockMoveValuation::where('stock_move_id', $scrapMove->id)->first();
        $this->assertNotNull($valuation);
        $journalEntry = $valuation->journalEntry;

        $this->assertNotNull($journalEntry);

        // Debit Scrap Account
        $this->assertTrue(
            $journalEntry->lines->contains(function ($line) use ($scrapAccount) {
                return $line->account_id === $scrapAccount->id && $line->debit->getAmount()->toFloat() > 0;
            }),
            'Journal entry should debit the scrap account'
        );

        // Credit Inventory
        $this->assertTrue(
            $journalEntry->lines->contains(function ($line) use ($inventoryAccount) {
                return $line->account_id === $inventoryAccount->id && $line->credit->getAmount()->toFloat() > 0;
            }),
            'Journal entry should credit the inventory account'
        );
    }

    public function test_it_throws_exception_if_no_scrap_location()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No Scrap location found');

        $mo = ManufacturingOrder::factory()->create();

        app(ScrapManufacturingAction::class)->execute($mo, []);
    }
}
