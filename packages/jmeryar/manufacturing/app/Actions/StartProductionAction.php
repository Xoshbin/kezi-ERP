<?php

namespace Jmeryar\Manufacturing\Actions;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Jmeryar\Inventory\Services\Inventory\StockMoveService;
use Jmeryar\Manufacturing\Enums\ManufacturingOrderStatus;
use Jmeryar\Manufacturing\Models\ManufacturingOrder;

class StartProductionAction
{
    public function __construct(
        private readonly StockMoveService $stockMoveService,
    ) {}

    public function execute(ManufacturingOrder $mo): ManufacturingOrder
    {
        return DB::transaction(function () use ($mo) {
            // Validate current status
            if ($mo->status !== ManufacturingOrderStatus::Confirmed) {
                throw new \InvalidArgumentException('Only confirmed manufacturing orders can be started.');
            }

            // Update MO status and start time
            $mo->update([
                'status' => ManufacturingOrderStatus::InProgress,
                'actual_start_date' => Carbon::now(),
            ]);

            // Update work orders to ready/in_progress
            $mo->workOrders()->where('status', 'pending')->update([
                'status' => 'ready',
            ]);

            return $mo->fresh();
        });
    }
}
