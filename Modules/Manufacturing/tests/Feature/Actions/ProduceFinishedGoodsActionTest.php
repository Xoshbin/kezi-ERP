<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Services\Inventory\StockMoveService;
use Modules\Manufacturing\Actions\ProduceFinishedGoodsAction;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Models\BillOfMaterial;
use Modules\Manufacturing\Models\ManufacturingOrder;
use Modules\Manufacturing\Models\ManufacturingOrderLine;
use Modules\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

/** @var \App\Models\Company $company */
/** @var \App\Models\User $user */
beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

describe('ProduceFinishedGoodsAction', function () {
    it('completes production for an in-progress manufacturing order', function () {
        // Arrange
        $finishedProduct = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $finishedProduct->id,
        ]);

        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'bom_id' => $bom->id,
            'product_id' => $finishedProduct->id,
            'status' => ManufacturingOrderStatus::InProgress,
            'quantity_to_produce' => 10.0,
            'source_location_id' => 1, // Dummy location IDs, StockMoveService should be mocked or handle it
            'destination_location_id' => 2,
        ]);

        // Create component line
        $component = Product::factory()->create(['company_id' => $this->company->id]);
        ManufacturingOrderLine::create([
            'company_id' => $this->company->id,
            'manufacturing_order_id' => $mo->id,
            'product_id' => $component->id,
            'quantity_required' => 20.0,
            'quantity_consumed' => 20.0,
            'unit_cost' => 5000, // 5.000 IQD
            'currency_code' => $this->company->currency->code,
        ]);

        // Mock StockMoveService
        $mockStockMoveService = mock(StockMoveService::class);
        $mockStockMoveService->shouldReceive('createMove')->once()->andReturn(new \Modules\Inventory\Models\StockMove);
        $this->app->instance(StockMoveService::class, $mockStockMoveService);

        // Act
        $action = app(ProduceFinishedGoodsAction::class);
        $updatedMo = $action->execute($mo);

        // Assert
        expect($updatedMo->status)->toBe(ManufacturingOrderStatus::Done);
        expect((float) $updatedMo->quantity_produced)->toBe(10.0);
        expect($updatedMo->actual_end_date)->not->toBeNull();

        $this->assertDatabaseHas('manufacturing_orders', [
            'id' => $mo->id,
            'status' => ManufacturingOrderStatus::Done->value,
            'quantity_produced' => 10.0,
        ]);
    });

    it('throws exception if MO is not in-progress', function () {
        // Arrange
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::Confirmed,
        ]);

        // Act & Assert
        $action = app(ProduceFinishedGoodsAction::class);

        expect(fn () => $action->execute($mo))
            ->toThrow(InvalidArgumentException::class, 'Only in-progress manufacturing orders can produce finished goods.');
    });

    it('throws exception if MO has no lines', function () {
        // Arrange
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::InProgress,
            'number' => 'MO-TEST-99',
        ]);

        // Act & Assert
        $action = app(ProduceFinishedGoodsAction::class);

        expect(fn () => $action->execute($mo))
            ->toThrow(RuntimeException::class, 'Manufacturing Order MO-TEST-99 has no lines to process.');
    });
});
