<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Manufacturing\Actions\ScheduleWorkOrdersAction;
use Modules\Manufacturing\Models\ManufacturingOrder;
use Modules\Manufacturing\Models\WorkCenter;
use Modules\Manufacturing\Models\WorkOrder;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

/** @var \Tests\TestCase&\Tests\Traits\WithConfiguredCompany $this */
beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

describe('ScheduleWorkOrdersAction', function () {
    it('schedules work orders sequentially based on manufacturing order planned date', function () {
        // Arrange
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'planned_start_date' => Carbon::parse('2026-02-01'),
        ]);

        $wc = WorkCenter::factory()->create(['company_id' => $this->company->id]);

        // WO1: 2 hours
        $wo1 = WorkOrder::factory()->create([
            'company_id' => $this->company->id,
            'manufacturing_order_id' => $mo->id,
            'work_center_id' => $wc->id,
            'sequence' => 1,
            'planned_duration' => 2.0,
        ]);

        // WO2: 1.5 hours
        $wo2 = WorkOrder::factory()->create([
            'company_id' => $this->company->id,
            'manufacturing_order_id' => $mo->id,
            'work_center_id' => $wc->id,
            'sequence' => 2,
            'planned_duration' => 1.5,
        ]);

        // Act
        $action = new ScheduleWorkOrdersAction;
        $action->execute($mo);

        // Assert
        $wo1->refresh();
        $wo2->refresh();

        $expectedStart1 = Carbon::parse('2026-02-01 00:00:00');
        $expectedFinish1 = Carbon::parse('2026-02-01 02:00:00');

        $expectedStart2 = $expectedFinish1;
        $expectedFinish2 = Carbon::parse('2026-02-01 03:30:00');

        expect($wo1->planned_start_at->toDateTimeString())->toBe($expectedStart1->toDateTimeString());
        expect($wo1->planned_finished_at->toDateTimeString())->toBe($expectedFinish1->toDateTimeString());

        expect($wo2->planned_start_at->toDateTimeString())->toBe($expectedStart2->toDateTimeString());
        expect($wo2->planned_finished_at->toDateTimeString())->toBe($expectedFinish2->toDateTimeString());
    });

    it('schedules work orders using now() if MO planned date is not set', function () {
        // Arrange
        $now = Carbon::parse('2026-01-23 22:00:00');
        Carbon::setTestNow($now);

        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'planned_start_date' => null,
        ]);

        $wc = WorkCenter::factory()->create(['company_id' => $this->company->id]);

        $wo = WorkOrder::factory()->create([
            'company_id' => $this->company->id,
            'manufacturing_order_id' => $mo->id,
            'work_center_id' => $wc->id,
            'planned_duration' => 1.0,
        ]);

        // Act
        $action = new ScheduleWorkOrdersAction;
        $action->execute($mo);

        // Assert
        $wo->refresh();

        expect($wo->planned_start_at->toDateTimeString())->toBe($now->toDateTimeString());
        expect($wo->planned_finished_at->toDateTimeString())->toBe($now->copy()->addHour()->toDateTimeString());

        Carbon::setTestNow(); // Reset
    });

    it('handles zero duration work orders', function () {
        // Arrange
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'planned_start_date' => Carbon::parse('2026-02-01'),
        ]);

        $wc = WorkCenter::factory()->create(['company_id' => $this->company->id]);

        $wo = WorkOrder::factory()->create([
            'company_id' => $this->company->id,
            'manufacturing_order_id' => $mo->id,
            'work_center_id' => $wc->id,
            'planned_duration' => 0,
        ]);

        // Act
        $action = new ScheduleWorkOrdersAction;
        $action->execute($mo);

        // Assert
        $wo->refresh();

        expect($wo->planned_start_at->toDateTimeString())->toBe(Carbon::parse('2026-02-01 00:00:00')->toDateTimeString());
        expect($wo->planned_finished_at->toDateTimeString())->toBe($wo->planned_start_at->toDateTimeString());
    });
});
