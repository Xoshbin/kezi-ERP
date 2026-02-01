<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Manufacturing\Enums\WorkOrderStatus;
use Jmeryar\Manufacturing\Models\ManufacturingOrder;
use Jmeryar\Manufacturing\Models\WorkCenter;
use Jmeryar\Manufacturing\Models\WorkOrder;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

/** @var \Tests\TestCase&\Tests\Traits\WithConfiguredCompany $this */
beforeEach(function () {
    /** @var \Tests\TestCase&\Tests\Traits\WithConfiguredCompany $this */
    $this->setupWithConfiguredCompany();
});

describe('WorkOrder Model', function () {
    it('belongs to a company', function () {
        $workOrder = WorkOrder::factory()->create(['company_id' => $this->company->id]);
        expect($workOrder->company)->toBeInstanceOf(\App\Models\Company::class);
        expect($workOrder->company_id)->toBe($this->company->id);
    });

    it('belongs to a manufacturing order', function () {
        $mo = ManufacturingOrder::factory()->create(['company_id' => $this->company->id]);
        $workOrder = WorkOrder::factory()->create([
            'company_id' => $this->company->id,
            'manufacturing_order_id' => $mo->id,
        ]);

        expect($workOrder->manufacturingOrder)->toBeInstanceOf(ManufacturingOrder::class);
        expect($workOrder->manufacturing_order_id)->toBe($mo->id);
    });

    it('belongs to a work center', function () {
        $workCenter = WorkCenter::factory()->create(['company_id' => $this->company->id]);
        $workOrder = WorkOrder::factory()->create([
            'company_id' => $this->company->id,
            'work_center_id' => $workCenter->id,
        ]);

        expect($workOrder->workCenter)->toBeInstanceOf(WorkCenter::class);
        expect($workOrder->work_center_id)->toBe($workCenter->id);
    });

    it('casts status and date fields', function () {
        $workOrder = WorkOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => WorkOrderStatus::InProgress,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        expect($workOrder->status)->toBeInstanceOf(WorkOrderStatus::class);
        expect($workOrder->status)->toBe(WorkOrderStatus::InProgress);
        expect($workOrder->started_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
        expect($workOrder->completed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    });
});
