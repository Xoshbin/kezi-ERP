<?php

namespace Jmeryar\Manufacturing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Jmeryar\Inventory\Enums\Inventory\StockMoveStatus;
use Jmeryar\Inventory\Enums\Inventory\StockMoveType;
use Jmeryar\Inventory\Models\StockMove;
use Jmeryar\Manufacturing\Actions\CancelManufacturingOrderAction;
use Jmeryar\Manufacturing\Enums\ManufacturingOrderStatus;
use Jmeryar\Manufacturing\Enums\WorkOrderStatus;
use Jmeryar\Manufacturing\Models\ManufacturingOrder;
use Jmeryar\Manufacturing\Models\WorkOrder;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class, RefreshDatabase::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->user = \App\Models\User::factory()->create();
});

it('can cancel a confirmed manufacturing order and releases reserved stock', function () {
    // Arrange: Create MO in Confirmed state
    $mo = ManufacturingOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => ManufacturingOrderStatus::Confirmed,
    ]);

    // Simulate stock reservation by creating a linked Stock Move in Confirmed status
    // Note: In a real flow, this might be created by ConfirmAction or similar, but we simulate it here
    $stockMove = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'source_type' => ManufacturingOrder::class,
        'source_id' => $mo->id,
        'status' => StockMoveStatus::Confirmed,
        'move_type' => StockMoveType::InternalTransfer,
    ]);

    // Act
    $result = app(CancelManufacturingOrderAction::class)->execute($mo);

    // Assert
    expect($result->status)->toBe(ManufacturingOrderStatus::Cancelled);

    // Check stock move is cancelled
    expect($stockMove->refresh()->status)->toBe(StockMoveStatus::Cancelled);
});

it('cancels associated work orders when manufacturing order is cancelled', function () {
    // Arrange: MO In Progress with Work Orders
    $mo = ManufacturingOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => ManufacturingOrderStatus::InProgress,
    ]);

    $workOrder1 = WorkOrder::factory()->create([
        'manufacturing_order_id' => $mo->id,
        'status' => WorkOrderStatus::Pending,
    ]);

    $workOrder2 = WorkOrder::factory()->create([
        'manufacturing_order_id' => $mo->id,
        'status' => WorkOrderStatus::InProgress,
    ]);

    $workOrderDone = WorkOrder::factory()->create([
        'manufacturing_order_id' => $mo->id,
        'status' => WorkOrderStatus::Done, // Should NOT be changed?
    ]);

    // Act
    app(CancelManufacturingOrderAction::class)->execute($mo);

    // Assert
    expect($workOrder1->refresh()->status)->toBe(WorkOrderStatus::Cancelled)
        ->and($workOrder2->refresh()->status)->toBe(WorkOrderStatus::Cancelled)
        ->and($workOrderDone->refresh()->status)->toBe(WorkOrderStatus::Done);
});

it('prevents cancellation if components have been consumed', function () {
    // Arrange
    $mo = ManufacturingOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => ManufacturingOrderStatus::InProgress,
    ]);

    // Create a DONE stock move linked to MO (consumption)
    StockMove::factory()->create([
        'company_id' => $this->company->id,
        'source_type' => ManufacturingOrder::class,
        'source_id' => $mo->id,
        'status' => StockMoveStatus::Done,
    ]);

    // Act & Assert
    expect(fn () => app(CancelManufacturingOrderAction::class)->execute($mo))
        ->toThrow(ValidationException::class, 'components have already been consumed');
});

it('prevents cancellation if finished goods have been produced', function () {
    // Arrange
    $mo = ManufacturingOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => ManufacturingOrderStatus::InProgress,
        'quantity_produced' => 5, // Some produced
    ]);

    // Act & Assert
    expect(fn () => app(CancelManufacturingOrderAction::class)->execute($mo))
        ->toThrow(ValidationException::class, 'finished goods have already been produced');
});

it('prevents cancellation of Done orders', function () {
    // Arrange
    $mo = ManufacturingOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => ManufacturingOrderStatus::Done,
    ]);

    // Act & Assert
    expect(fn () => app(CancelManufacturingOrderAction::class)->execute($mo))
        ->toThrow(ValidationException::class);
});
