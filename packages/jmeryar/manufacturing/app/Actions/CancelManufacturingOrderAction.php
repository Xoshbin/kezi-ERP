<?php

namespace Jmeryar\Manufacturing\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Jmeryar\Inventory\Enums\Inventory\StockMoveStatus;
use Jmeryar\Inventory\Models\StockMove;
use Jmeryar\Inventory\Services\Inventory\StockMoveService;
use Jmeryar\Inventory\Services\Inventory\StockReservationService;
use Jmeryar\Manufacturing\Enums\ManufacturingOrderStatus;
use Jmeryar\Manufacturing\Enums\WorkOrderStatus;
use Jmeryar\Manufacturing\Models\ManufacturingOrder;

class CancelManufacturingOrderAction
{
    public function __construct(
        private readonly StockReservationService $stockReservationService,
        private readonly StockMoveService $stockMoveService,
    ) {}

    public function execute(ManufacturingOrder $mo): ManufacturingOrder
    {
        return DB::transaction(function () use ($mo) {
            // 1. Validation: Ensure an MO can only be cancelled in Draft, Confirmed, or In Progress states.
            if (! in_array($mo->status, [
                ManufacturingOrderStatus::Draft,
                ManufacturingOrderStatus::Confirmed,
                ManufacturingOrderStatus::InProgress,
            ])) {
                throw ValidationException::withMessages([
                    'status' => "Manufacturing Order cannot be cancelled in {$mo->status->value} state.",
                ]);
            }

            // 2. Constraints: Block cancellation if components consumed or finished goods produced.
            // Check for produced quantity
            if ($mo->quantity_produced > 0) {
                throw ValidationException::withMessages([
                    'quantity_produced' => 'Cannot cancel Manufacturing Order because finished goods have already been produced. Please use the Return flow.',
                ]);
            }

            // Check for consumed components (Stock Moves linked to MO that are Done)
            $linkedMoves = StockMove::query()
                ->where('source_type', ManufacturingOrder::class)
                ->where('source_id', $mo->id)
                ->get();

            if ($linkedMoves->where('status', StockMoveStatus::Done)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'components' => 'Cannot cancel Manufacturing Order because components have already been consumed. Please use the Return flow.',
                ]);
            }

            // 3. Inventory Cleanup: Release all reserved stock quantities.
            // Iterate over non-done moves and cancel/unreserve them.
            $pendingMoves = $linkedMoves->where('status', '!=', StockMoveStatus::Done);

            foreach ($pendingMoves as $move) {
                // Release reservation first
                $this->stockReservationService->releaseForMove($move);
                // Cancel the move itself
                $this->stockMoveService->cancelMove($move);
            }

            // 4. Work Orders: Cancel pending/in-progress Work Orders.
            $mo->workOrders()
                ->whereIn('status', [
                    WorkOrderStatus::Pending,
                    WorkOrderStatus::Ready,
                    WorkOrderStatus::InProgress,
                ])
                ->update(['status' => WorkOrderStatus::Cancelled]);

            // 5. State Change: Update MO status to Cancelled.
            $mo->update([
                'status' => ManufacturingOrderStatus::Cancelled,
            ]);

            return $mo->refresh();
        });
    }
}
