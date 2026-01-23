<?php

namespace Modules\Manufacturing\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Manufacturing\Models\ManufacturingOrder;

class ScheduleWorkOrdersAction
{
    /**
     * Schedule work orders sequentially for a given manufacturing order.
     */
    public function execute(ManufacturingOrder $mo): void
    {
        DB::transaction(function () use ($mo) {
            $workOrders = $mo->workOrders()
                ->orderBy('sequence')
                ->orderBy('id')
                ->get();

            // Determine initial start time: MO's planned date or now()
            $currentTime = $mo->planned_start_date
                ? Carbon::parse($mo->planned_start_date)->startOfDay()
                : Carbon::now();

            foreach ($workOrders as $workOrder) {
                $workOrder->planned_start_at = $currentTime;

                // Duration is stored in hours in the database
                $durationHours = (float) ($workOrder->planned_duration ?? 0);
                $durationMinutes = (int) ($durationHours * 60);

                $workOrder->planned_finished_at = $currentTime->copy()->addMinutes($durationMinutes);

                $workOrder->save();

                // Next work order starts when this one finishes
                $currentTime = $workOrder->planned_finished_at;
            }
        });
    }
}
