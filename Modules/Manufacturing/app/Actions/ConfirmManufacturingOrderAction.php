<?php

namespace Modules\Manufacturing\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Models\ManufacturingOrder;

class ConfirmManufacturingOrderAction
{
    public function __construct(
        private readonly ScheduleWorkOrdersAction $scheduleWorkOrdersAction
    ) {}

    public function execute(ManufacturingOrder $mo): ManufacturingOrder
    {
        return DB::transaction(function () use ($mo) {
            // Validate current status
            if ($mo->status !== ManufacturingOrderStatus::Draft) {
                throw new \InvalidArgumentException('Only draft manufacturing orders can be confirmed.');
            }

            // Update status
            $mo->update([
                'status' => ManufacturingOrderStatus::Confirmed,
            ]);

            // Create work order for single-operation BOM
            $bom = $mo->billOfMaterial()->with('lines.workCenter')->first();

            // Get the first work center from BOM lines (single-operation)
            $workCenter = $bom->lines->whereNotNull('work_center_id')->first()?->workCenter;

            if ($workCenter) {
                $mo->workOrders()->create([
                    'company_id' => $mo->company_id,
                    'work_center_id' => $workCenter->id,
                    'sequence' => 1,
                    'name' => "Production: {$mo->product->name}",
                    'status' => 'pending',
                    'planned_duration' => 1.0, // Default duration to ensure scheduling works
                ]);
            }

            // Schedule work orders
            $this->scheduleWorkOrdersAction->execute($mo);

            return $mo->fresh(['workOrders', 'lines']);
        });
    }
}
