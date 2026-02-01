<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Manufacturing\Actions\StartProductionAction;
use Jmeryar\Manufacturing\Enums\ManufacturingOrderStatus;
use Jmeryar\Manufacturing\Enums\WorkOrderStatus;
use Jmeryar\Manufacturing\Models\BillOfMaterial;
use Jmeryar\Manufacturing\Models\ManufacturingOrder;
use Jmeryar\Manufacturing\Models\WorkCenter;
use Jmeryar\Manufacturing\Models\WorkOrder;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

/** @var \App\Models\Company $company */
/** @var \App\Models\User $user */
beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

describe('StartProductionAction', function () {
    it('starts production for a confirmed manufacturing order', function () {
        // Arrange
        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'bom_id' => $bom->id,
            'product_id' => $bom->product_id,
            'status' => ManufacturingOrderStatus::Confirmed,
        ]);

        // Act
        $action = app(StartProductionAction::class);
        $updatedMo = $action->execute($mo);

        // Assert
        expect($updatedMo->status)->toBe(ManufacturingOrderStatus::InProgress);
        expect($updatedMo->actual_start_date)->not->toBeNull();

        $this->assertDatabaseHas('manufacturing_orders', [
            'id' => $mo->id,
            'status' => ManufacturingOrderStatus::InProgress->value,
        ]);
    });

    it('updates work orders to ready status', function () {
        // Arrange
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::Confirmed,
        ]);

        $workCenter = WorkCenter::factory()->create(['company_id' => $this->company->id]);

        $workOrder = WorkOrder::factory()->create([
            'company_id' => $this->company->id,
            'manufacturing_order_id' => $mo->id,
            'work_center_id' => $workCenter->id,
            'status' => WorkOrderStatus::Pending,
        ]);

        // Act
        $action = app(StartProductionAction::class);
        $action->execute($mo);

        // Assert
        expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::Ready);

        $this->assertDatabaseHas('work_orders', [
            'id' => $workOrder->id,
            'status' => WorkOrderStatus::Ready->value,
        ]);
    });

    it('throws exception if MO is not confirmed', function () {
        // Arrange
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::Draft,
        ]);

        // Act & Assert
        $action = app(StartProductionAction::class);

        expect(fn () => $action->execute($mo))
            ->toThrow(InvalidArgumentException::class, 'Only confirmed manufacturing orders can be started.');
    });
});
