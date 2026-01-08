<?php

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\JournalEntry;
use Modules\Inventory\Models\StockLocation;
use Modules\Manufacturing\Actions\Accounting\CreateJournalEntryForManufacturingAction;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Models\BillOfMaterial;
use Modules\Manufacturing\Models\ManufacturingOrder;
use Modules\Manufacturing\Models\ManufacturingOrderLine;
use Modules\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    // Create necessary accounts for manufacturing
    $this->finishedGoodsAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1510',
        'name' => 'Finished Goods Inventory',
        'type' => 'current_assets',
    ]);

    $this->rawMaterialsAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1520',
        'name' => 'Raw Materials Inventory',
        'type' => 'current_assets',
    ]);

    $this->manufacturingJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'short_code' => 'MFG',
        'name' => 'Manufacturing Journal',
        'type' => 'miscellaneous',
    ]);

    // Configure company with manufacturing accounts
    $this->company->update([
        'default_finished_goods_inventory_id' => $this->finishedGoodsAccount->id,
        'default_raw_materials_inventory_id' => $this->rawMaterialsAccount->id,
        'default_manufacturing_journal_id' => $this->manufacturingJournal->id,
    ]);

    // Create stock locations
    $this->sourceLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Raw Materials Warehouse',
        'type' => 'internal',
    ]);

    $this->destinationLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Finished Goods Warehouse',
        'type' => 'internal',
    ]);
});

describe('Manufacturing Accounting Integration', function () {
    it('creates journal entry when manufacturing order is completed', function () {
        // Create products
        $finishedProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Finished Widget',
        ]);

        $componentProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Component A',
        ]);

        // Create BOM
        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $finishedProduct->id,
            'code' => 'BOM-001',
        ]);

        // Create Manufacturing Order
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'bom_id' => $bom->id,
            'product_id' => $finishedProduct->id,
            'number' => 'MO-001',
            'quantity_to_produce' => 10.0,
            'quantity_produced' => 10.0,
            'status' => ManufacturingOrderStatus::Done,
            'source_location_id' => $this->sourceLocation->id,
            'destination_location_id' => $this->destinationLocation->id,
        ]);

        // Create MO lines (components consumed)
        ManufacturingOrderLine::create([
            'company_id' => $this->company->id,
            'manufacturing_order_id' => $mo->id,
            'product_id' => $componentProduct->id,
            'quantity_required' => 20.0,
            'quantity_consumed' => 20.0,
            'unit_cost' => 5.00, // $5 per unit
            'currency_code' => $this->company->currency->code,
        ]);

        // Execute the action
        $action = app(CreateJournalEntryForManufacturingAction::class);
        $journalEntry = $action->execute($mo, $this->user);

        // Assert journal entry was created
        expect($journalEntry)->toBeInstanceOf(JournalEntry::class);
        expect($journalEntry->company_id)->toBe($this->company->id);
        expect($journalEntry->journal_id)->toBe($this->manufacturingJournal->id);
        expect($journalEntry->reference)->toBe('MO-001');
        expect($journalEntry->is_posted)->toBeTrue();
        expect($journalEntry->source_type)->toBe(ManufacturingOrder::class);
        expect($journalEntry->source_id)->toBe($mo->id);

        // Assert journal entry has correct lines
        expect($journalEntry->lines)->toHaveCount(2);

        // Find debit and credit lines
        $debitLine = $journalEntry->lines->first(fn ($line) => $line->debit->isPositive());
        $creditLine = $journalEntry->lines->first(fn ($line) => $line->credit->isPositive());

        // Assert debit to Finished Goods
        expect($debitLine->account_id)->toBe($this->finishedGoodsAccount->id);
        expect($debitLine->debit->getMinorAmount()->toInt())->toBe(10000); // $100 (20 units * $5)
        expect($debitLine->credit->isZero())->toBeTrue();

        // Assert credit to Raw Materials
        expect($creditLine->account_id)->toBe($this->rawMaterialsAccount->id);
        expect($creditLine->credit->getMinorAmount()->toInt())->toBe(10000); // $100
        expect($creditLine->debit->isZero())->toBeTrue();

        // Assert entry is balanced
        $totalDebit = $journalEntry->lines->sum(fn ($line) => $line->debit->getMinorAmount()->toInt());
        $totalCredit = $journalEntry->lines->sum(fn ($line) => $line->credit->getMinorAmount()->toInt());
        expect($totalDebit)->toBe($totalCredit);
    });

    it('throws exception when manufacturing accounts are not configured', function () {
        // Remove manufacturing account configuration
        $this->company->update([
            'default_finished_goods_inventory_id' => null,
            'default_raw_materials_inventory_id' => null,
            'default_manufacturing_journal_id' => null,
        ]);

        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::Done,
        ]);

        $action = app(CreateJournalEntryForManufacturingAction::class);

        expect(fn () => $action->execute($mo, $this->user))
            ->toThrow(RuntimeException::class, 'Manufacturing accounts');
    });

    it('creates journal entry with multiple components', function () {
        $finishedProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Complex Widget',
        ]);

        $component1 = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Component A',
        ]);

        $component2 = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Component B',
        ]);

        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $finishedProduct->id,
        ]);

        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'bom_id' => $bom->id,
            'product_id' => $finishedProduct->id,
            'quantity_to_produce' => 5.0,
            'quantity_produced' => 5.0,
            'status' => ManufacturingOrderStatus::Done,
            'source_location_id' => $this->sourceLocation->id,
            'destination_location_id' => $this->destinationLocation->id,
        ]);

        // Create multiple component lines
        ManufacturingOrderLine::create([
            'company_id' => $this->company->id,
            'manufacturing_order_id' => $mo->id,
            'product_id' => $component1->id,
            'quantity_required' => 10.0,
            'quantity_consumed' => 10.0,
            'unit_cost' => 3.00,
            'currency_code' => $this->company->currency->code,
        ]);

        ManufacturingOrderLine::create([
            'company_id' => $this->company->id,
            'manufacturing_order_id' => $mo->id,
            'product_id' => $component2->id,
            'quantity_required' => 15.0,
            'quantity_consumed' => 15.0,
            'unit_cost' => 2.00,
            'currency_code' => $this->company->currency->code,
        ]);

        $action = app(CreateJournalEntryForManufacturingAction::class);
        $journalEntry = $action->execute($mo, $this->user);

        // Should have 3 lines: 2 credits (components) + 1 debit (finished goods)
        expect($journalEntry->lines)->toHaveCount(3);

        $creditLines = $journalEntry->lines->filter(fn ($line) => $line->credit->isPositive());
        $debitLines = $journalEntry->lines->filter(fn ($line) => $line->debit->isPositive());

        expect($creditLines)->toHaveCount(2);
        expect($debitLines)->toHaveCount(1);

        // Total cost = (10 * $3) + (15 * $2) = $30 + $30 = $60
        $totalCost = $debitLines->first()->debit->getMinorAmount()->toInt();
        expect($totalCost)->toBe(6000); // $60 in minor units
    });

    it('links journal entry to manufacturing order', function () {
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::Done,
            'source_location_id' => $this->sourceLocation->id,
            'destination_location_id' => $this->destinationLocation->id,
        ]);

        ManufacturingOrderLine::create([
            'company_id' => $this->company->id,
            'manufacturing_order_id' => $mo->id,
            'product_id' => Product::factory()->create(['company_id' => $this->company->id])->id,
            'quantity_required' => 5.0,
            'quantity_consumed' => 5.0,
            'unit_cost' => 10.00,
            'currency_code' => $this->company->currency->code,
        ]);

        $action = app(CreateJournalEntryForManufacturingAction::class);
        $journalEntry = $action->execute($mo, $this->user);

        // Verify the link
        expect($journalEntry->source_type)->toBe(ManufacturingOrder::class);
        expect($journalEntry->source_id)->toBe($mo->id);

        // Verify reverse relationship
        $mo->refresh();
        expect($mo->journalEntry)->not->toBeNull();
        expect($mo->journalEntry->id)->toBe($journalEntry->id);
    });
});
