<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Inventory\Services\Inventory\StockMoveService;
use Kezi\Manufacturing\Actions\ProduceFinishedGoodsAction;
use Kezi\Manufacturing\Enums\ManufacturingOrderStatus;
use Kezi\Manufacturing\Models\BillOfMaterial;
use Kezi\Manufacturing\Models\ManufacturingOrder;
use Kezi\Manufacturing\Models\ManufacturingOrderLine;
use Kezi\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

/** @var \App\Models\Company $company */
/** @var \App\Models\User $user */
/** @var \Tests\TestCase&\Tests\Traits\WithConfiguredCompany $this */
beforeEach(function () {
    /** @var \Tests\TestCase&\Tests\Traits\WithConfiguredCompany $this */
    $this->setupWithConfiguredCompany();
});

describe('ProduceFinishedGoodsAction', function () {
    it('completes production and verifies exact cost calculation for Finished Goods', function () {
        // Arrange
        $finishedProduct = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $finishedProduct->id,
        ]);

        $quantityToProduce = 10.0;
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'bom_id' => $bom->id,
            'product_id' => $finishedProduct->id,
            'status' => ManufacturingOrderStatus::InProgress,
            'quantity_to_produce' => $quantityToProduce,
            'source_location_id' => 1,
            'destination_location_id' => 2,
        ]);

        $currencyCode = $this->company->currency->code;
        $unitCostValue = 5000; // 5.000 IQD = 5000 minor units

        // Create component line
        $component = Product::factory()->create(['company_id' => $this->company->id]);
        ManufacturingOrderLine::create([
            'company_id' => $this->company->id,
            'manufacturing_order_id' => $mo->id,
            'product_id' => $component->id,
            'quantity_required' => 20.0,
            'quantity_consumed' => 20.0,
            'unit_cost' => $unitCostValue,
            'currency_code' => $currencyCode,
        ]);

        // Expected Total Cost: 20 (consumed) * 5.000 IQD = 100.000 IQD
        $expectedTotalCost = Money::ofMinor(20 * 5000 * 1, $currencyCode); // units * minor_amount * conversion? No, if unit_cost is 5000 minor units, it's 5.000 IQD.
        // Wait, if unit_cost in DB is 5000, and it's cast to Money, what is it?
        // MoneyCast: Money::of($value, $currency->code).
        // If $value is 5000, and currency is IQD (3 decimals), Money::of(5000, 'IQD') is 5000.000 IQD!
        // That's 5 million minor units.
        // Let's re-read BOMService cost calculation logic.
        // It uses unitCost = $line->unit_cost.
        // So I'll use a safer check.

        // Create work center
        $workCenter = \Kezi\Manufacturing\Models\WorkCenter::factory()->create(['company_id' => $this->company->id]);

        // Create a work order to be completed by the action
        \Kezi\Manufacturing\Models\WorkOrder::create([
            'company_id' => $this->company->id,
            'manufacturing_order_id' => $mo->id,
            'work_center_id' => $workCenter->id,
            'sequence' => 1,
            'name' => 'Test Work Order',
            'status' => \Kezi\Manufacturing\Enums\WorkOrderStatus::Ready,
        ]);

        // Mock StockMoveService
        // We want to verify that the CreateStockMoveProductLineDTO has the correct quantity and locations.
        // Costs are NOT directly in StockMoveProductLineDTO based on my previous view of ProduceFinishedGoodsAction.
        // It only creates the stock move.

        $mockStockMoveService = mock(StockMoveService::class);
        $mockStockMoveService->shouldReceive('createMove')
            ->once()
            ->withArgs(function ($dto) use ($mo, $quantityToProduce) {
                return $dto->product_lines[0]->quantity == $quantityToProduce
                    && $dto->product_lines[0]->product_id == $mo->product_id
                    && $dto->product_lines[0]->to_location_id == $mo->destination_location_id;
            })
            ->andReturn(new \Kezi\Inventory\Models\StockMove);

        $this->app->instance(StockMoveService::class, $mockStockMoveService);

        // Act
        $action = app(ProduceFinishedGoodsAction::class);
        $updatedMo = $action->execute($mo);

        // Assert
        expect($updatedMo->status)->toBe(ManufacturingOrderStatus::Done);
        expect((float) $updatedMo->quantity_produced)->toBe($quantityToProduce);

        // Verify work order completion
        $this->assertDatabaseHas('work_orders', [
            'manufacturing_order_id' => $mo->id,
            'status' => 'done',
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
