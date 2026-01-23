<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Manufacturing\Actions\ConfirmManufacturingOrderAction;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Models\BillOfMaterial;
use Modules\Manufacturing\Models\BOMLine;
use Modules\Manufacturing\Models\ManufacturingOrder;
use Modules\Manufacturing\Models\WorkCenter;
use Modules\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

/** @var \Tests\TestCase&\Tests\Traits\WithConfiguredCompany $this */

/** @var \App\Models\Company $company */
/** @var \App\Models\User $user */
beforeEach(function () {
    /** @var \Tests\TestCase&\Tests\Traits\WithConfiguredCompany $this */
    $this->setupWithConfiguredCompany();
});

describe('ConfirmManufacturingOrderAction', function () {
    it('confirms a draft manufacturing order', function () {
        // Arrange
        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'bom_id' => $bom->id,
            'product_id' => $bom->product_id,
            'status' => ManufacturingOrderStatus::Draft,
        ]);

        // Act
        $action = app(ConfirmManufacturingOrderAction::class);
        $updatedMo = $action->execute($mo);

        // Assert
        expect($updatedMo->status)->toBe(ManufacturingOrderStatus::Confirmed);
        $this->assertDatabaseHas('manufacturing_orders', [
            'id' => $mo->id,
            'status' => ManufacturingOrderStatus::Confirmed->value,
        ]);
    });

    it('creates a work order if a work center is assigned in BOM', function () {
        // Arrange
        $product = Product::factory()->create(['company_id' => $this->company->id]);
        $workCenter = WorkCenter::factory()->create(['company_id' => $this->company->id]);

        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
        ]);

        BOMLine::factory()->forBom($bom)->create([
            'company_id' => $this->company->id,
            'product_id' => Product::factory()->create(['company_id' => $this->company->id])->id,
            'work_center_id' => $workCenter->id,
        ]);

        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'bom_id' => $bom->id,
            'product_id' => $product->id,
            'status' => ManufacturingOrderStatus::Draft,
        ]);

        // Act
        $action = app(ConfirmManufacturingOrderAction::class);
        $updatedMo = $action->execute($mo);

        // Assert
        expect($updatedMo->workOrders)->toHaveCount(1);
        $workOrder = $updatedMo->workOrders->first();
        expect($workOrder->work_center_id)->toBe($workCenter->id);
        expect($workOrder->status)->toBe(Modules\Manufacturing\Enums\WorkOrderStatus::Pending);
        expect($workOrder->planned_start_at)->not->toBeNull();
        expect($workOrder->planned_finished_at)->not->toBeNull();

        $this->assertDatabaseHas('work_orders', [
            'manufacturing_order_id' => $mo->id,
            'work_center_id' => $workCenter->id,
            'status' => 'pending',
        ]);
    });

    it('throws exception if MO is not in draft status', function () {
        // Arrange
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::Confirmed,
        ]);

        // Act & Assert
        $action = app(ConfirmManufacturingOrderAction::class);

        expect(fn () => $action->execute($mo))
            ->toThrow(InvalidArgumentException::class, 'Only draft manufacturing orders can be confirmed.');
    });
});
