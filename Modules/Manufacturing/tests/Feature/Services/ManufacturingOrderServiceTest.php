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

beforeEach(function () {
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

    it('can complete production', function () {
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::InProgress,
        ]);

        // Add a line so it doesn't fail
        $mo->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => Product::factory()->create(['company_id' => $this->company->id])->id,
            'quantity_required' => 1,
            'quantity_consumed' => 1,
            'unit_cost' => 1000,
            'currency_code' => $this->company->currency->code,
        ]);

        $service = app(ManufacturingOrderService::class);

        // Mock auth user for journal entry creation
        $this->actingAs($this->user);

        $service->complete($mo);

        expect($mo->fresh()->status)->toBe(ManufacturingOrderStatus::Done);
        expect($mo->fresh()->actual_end_date)->not->toBeNull();
        expect($mo->fresh()->journal_entry_id)->not->toBeNull();
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
