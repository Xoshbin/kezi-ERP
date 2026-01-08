<?php

namespace Modules\Manufacturing\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Models\ManufacturingOrder;

class ConfirmManufacturingOrderAction
{
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
            $workCenter = $bom->lines->first()?->workCenter;

            if ($workCenter) {
                $mo->workOrders()->create([
                    'company_id' => $mo->company_id,
                    'work_center_id' => $workCenter->id,
                    'sequence' => 1,
                    'name' => "Production: {$mo->product->name}",
                    'status' => 'pending',
                    'planned_duration' => null, // Can be calculated based on quantity and work center capacity
                ]);
            }

            return $mo->fresh(['workOrders', 'lines']);
        });
    }
}
