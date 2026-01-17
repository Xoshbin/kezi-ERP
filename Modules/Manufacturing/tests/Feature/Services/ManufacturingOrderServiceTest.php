<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Accounting\JournalType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Inventory\Models\StockLocation;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Models\ManufacturingOrder;
use Modules\Manufacturing\Services\ManufacturingOrderService;
use Modules\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);
/** @var \Tests\TestCase&\Tests\Traits\WithConfiguredCompany $this */
beforeEach(function () {
    /** @var \Tests\TestCase&\Tests\Traits\WithConfiguredCompany $this */
    $this->setupWithConfiguredCompany();

    // Configure manufacturing accounts for the company
    $this->company->update([
        'default_finished_goods_inventory_id' => Account::factory()->for($this->company)->create(['type' => 'current_assets'])->id,
        'default_raw_materials_inventory_id' => Account::factory()->for($this->company)->create(['type' => 'current_assets'])->id,
        'default_manufacturing_journal_id' => Journal::factory()->for($this->company)->create(['type' => JournalType::Miscellaneous])->id,
    ]);

    $this->sourceLocation = StockLocation::factory()->create(['company_id' => $this->company->id]);
    $this->destLocation = StockLocation::factory()->create(['company_id' => $this->company->id]);
});

describe('ManufacturingOrderService', function () {
    it('can confirm a manufacturing order', function () {
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::Draft,
        ]);

        $service = app(ManufacturingOrderService::class);
        $service->confirm($mo);

        expect($mo->fresh()->status)->toBe(ManufacturingOrderStatus::Confirmed);
    });

    it('can start production', function () {
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::Confirmed,
        ]);

        $service = app(ManufacturingOrderService::class);
        $service->startProduction($mo);

        expect($mo->fresh()->status)->toBe(ManufacturingOrderStatus::InProgress);
        expect($mo->fresh()->actual_start_date)->not->toBeNull();
    });

    it('can complete production and verifies journal entry correctness', function () {
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::InProgress,
            'quantity_to_produce' => 10,
        ]);

        $currencyCode = $this->company->currency->code;
        $unitCostValue = 1000; // 1.000 IQD

        // Add a line so it doesn't fail
        $mo->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => Product::factory()->create(['company_id' => $this->company->id])->id,
            'quantity_required' => 5,
            'quantity_consumed' => 5,
            'unit_cost' => $unitCostValue,
            'currency_code' => $currencyCode,
        ]);

        $service = app(ManufacturingOrderService::class);

        // Mock auth user for journal entry creation
        $this->actingAs($this->user);

        $service->complete($mo);

        $mo = $mo->fresh(['journalEntry.lines']);

        expect($mo->status)->toBe(ManufacturingOrderStatus::Done);
        expect($mo->actual_end_date)->not->toBeNull();
        expect($mo->journal_entry_id)->not->toBeNull();

        $journalEntry = $mo->journalEntry;
        expect($journalEntry->reference)->toBe($mo->number);

        // Expected total cost: 5 (consumed) * 1.000 IQD = 5.000 IQD
        // In IQD (3 decimals), 5.000 IQD = 5,000,000 minor units.
        $expectedTotalCostMinor = 5000000;

        // Verify Journal Lines
        // Line 1: Credit Raw Materials (Credit)
        // Line 2: Debit Finished Goods (Debit)
        expect($journalEntry->lines)->toHaveCount(2);

        $rawMaterialLine = $journalEntry->lines->where('account_id', $this->company->default_raw_materials_inventory_id)->first();
        $finishedGoodsLine = $journalEntry->lines->where('account_id', $this->company->default_finished_goods_inventory_id)->first();

        expect($rawMaterialLine)->not->toBeNull();
        expect($finishedGoodsLine)->not->toBeNull();

        expect($rawMaterialLine->credit->getMinorAmount()->toInt())->toBe($expectedTotalCostMinor);
        expect($rawMaterialLine->debit->getMinorAmount()->toInt())->toBe(0);

        expect($finishedGoodsLine->debit->getMinorAmount()->toInt())->toBe($expectedTotalCostMinor);
        expect($finishedGoodsLine->credit->getMinorAmount()->toInt())->toBe(0);
    });

    it('can cancel a manufacturing order', function () {
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::Draft,
        ]);

        $service = app(ManufacturingOrderService::class);
        $service->cancel($mo);

        expect($mo->fresh()->status)->toBe(ManufacturingOrderStatus::Cancelled);
    });
});
