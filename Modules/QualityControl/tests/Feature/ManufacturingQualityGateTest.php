<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Services\Inventory\StockMoveService;
use Modules\Manufacturing\Actions\ProduceFinishedGoodsAction;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Models\ManufacturingOrder;
use Modules\Manufacturing\Models\ManufacturingOrderLine;
use Modules\QualityControl\Enums\QualityCheckStatus;
use Modules\QualityControl\Models\QualityCheck;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

describe('Manufacturing Quality Gate', function () {
    it('allows completion without checks', function () {
        // Arrange
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::InProgress,
            'quantity_to_produce' => 10,
        ]);

        // Add a line to avoid "no lines" error
        ManufacturingOrderLine::create([
            'company_id' => $this->company->id,
            'manufacturing_order_id' => $mo->id,
            'product_id' => $mo->product_id,
            'quantity_consumed' => 10,
            'quantity_required' => 10,
            'unit_cost' => 100,
            'currency_code' => $this->company->currency->code,
        ]);

        $mockStockMoveService = mock(StockMoveService::class);
        $mockStockMoveService->shouldReceive('createMove')->andReturn(new \Modules\Inventory\Models\StockMove);
        $this->app->instance(StockMoveService::class, $mockStockMoveService);

        // Act
        $action = app(ProduceFinishedGoodsAction::class);
        $updatedMo = $action->execute($mo);

        // Assert
        expect($updatedMo->status)->toBe(ManufacturingOrderStatus::Done);
    });

    it('blocks completion with pending mandatory checks', function () {
        // Arrange
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::InProgress,
        ]);

        ManufacturingOrderLine::create([
            'company_id' => $this->company->id,
            'manufacturing_order_id' => $mo->id,
            'product_id' => $mo->product_id,
            'quantity_consumed' => 10,
            'quantity_required' => 10,
            'unit_cost' => 100,
            'currency_code' => $this->company->currency->code,
        ]);

        // Create a mandatory pending check
        QualityCheck::create([
            'company_id' => $this->company->id,
            'number' => 'QC-PENDING',
            'product_id' => $mo->product_id,
            'source_type' => ManufacturingOrder::class,
            'source_id' => $mo->id,
            'status' => QualityCheckStatus::InProgress,
            'is_blocking' => true,
        ]);

        $mockStockMoveService = mock(StockMoveService::class);
        $this->app->instance(StockMoveService::class, $mockStockMoveService);

        // Act & Assert
        $action = app(ProduceFinishedGoodsAction::class);

        expect(fn () => $action->execute($mo))
            ->toThrow(Illuminate\Validation\ValidationException::class, 'All mandatory quality checks must be completed.');
    });

    it('allows completion with passed mandatory checks', function () {
        // Arrange
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::InProgress,
            'quantity_to_produce' => 10,
        ]);

        ManufacturingOrderLine::create([
            'company_id' => $this->company->id,
            'manufacturing_order_id' => $mo->id,
            'product_id' => $mo->product_id,
            'quantity_consumed' => 10,
            'quantity_required' => 10,
            'unit_cost' => 100,
            'currency_code' => $this->company->currency->code,
        ]);

        // Create a mandatory passed check
        QualityCheck::create([
            'company_id' => $this->company->id,
            'number' => 'QC-PASSED',
            'product_id' => $mo->product_id,
            'source_type' => ManufacturingOrder::class,
            'source_id' => $mo->id,
            'status' => QualityCheckStatus::Passed,
            'is_blocking' => true,
        ]);

        $mockStockMoveService = mock(StockMoveService::class);
        $mockStockMoveService->shouldReceive('createMove')->andReturn(new \Modules\Inventory\Models\StockMove);
        $this->app->instance(StockMoveService::class, $mockStockMoveService);

        // Act
        $action = app(ProduceFinishedGoodsAction::class);
        $updatedMo = $action->execute($mo);

        // Assert
        expect($updatedMo->status)->toBe(ManufacturingOrderStatus::Done);
    });

    it('blocks completion with failed mandatory checks', function () {
        // Arrange
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::InProgress,
        ]);

        ManufacturingOrderLine::create([
            'company_id' => $this->company->id,
            'manufacturing_order_id' => $mo->id,
            'product_id' => $mo->product_id,
            'quantity_consumed' => 10,
            'quantity_required' => 10,
            'unit_cost' => 100,
            'currency_code' => $this->company->currency->code,
        ]);

        // Create a mandatory failed check
        QualityCheck::create([
            'company_id' => $this->company->id,
            'number' => 'QC-FAILED',
            'product_id' => $mo->product_id,
            'source_type' => ManufacturingOrder::class,
            'source_id' => $mo->id,
            'status' => QualityCheckStatus::Failed,
            'is_blocking' => true,
        ]);

        $mockStockMoveService = mock(StockMoveService::class);
        $this->app->instance(StockMoveService::class, $mockStockMoveService);

        // Act & Assert
        $action = app(ProduceFinishedGoodsAction::class);

        expect(fn () => $action->execute($mo))
            ->toThrow(Illuminate\Validation\ValidationException::class, 'All mandatory quality checks must be passed.');
    });

    it('allows completion with pending non-blocking checks', function () {
        // Arrange
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::InProgress,
            'quantity_to_produce' => 10,
        ]);

        ManufacturingOrderLine::create([
            'company_id' => $this->company->id,
            'manufacturing_order_id' => $mo->id,
            'product_id' => $mo->product_id,
            'quantity_consumed' => 10,
            'quantity_required' => 10,
            'unit_cost' => 100,
            'currency_code' => $this->company->currency->code,
        ]);

        // Create a non-blocking pending check
        QualityCheck::create([
            'company_id' => $this->company->id,
            'number' => 'QC-OPTIONAL',
            'product_id' => $mo->product_id,
            'source_type' => ManufacturingOrder::class,
            'source_id' => $mo->id,
            'status' => QualityCheckStatus::InProgress,
            'is_blocking' => false,
        ]);

        $mockStockMoveService = mock(StockMoveService::class);
        $mockStockMoveService->shouldReceive('createMove')->andReturn(new \Modules\Inventory\Models\StockMove);
        $this->app->instance(StockMoveService::class, $mockStockMoveService);

        // Act
        $action = app(ProduceFinishedGoodsAction::class);
        $updatedMo = $action->execute($mo);

        // Assert
        expect($updatedMo->status)->toBe(ManufacturingOrderStatus::Done);
    });
});
