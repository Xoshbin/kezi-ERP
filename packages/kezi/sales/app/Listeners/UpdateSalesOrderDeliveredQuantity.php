<?php

namespace Kezi\Sales\Listeners;

use Kezi\Inventory\Events\Inventory\StockMoveConfirmed;
use Kezi\Sales\Models\SalesOrder;

class UpdateSalesOrderDeliveredQuantity
{
    public function handle(StockMoveConfirmed $event): void
    {
        $stockMove = $event->stockMove;

        if ($stockMove->source_type !== SalesOrder::class) {
            return;
        }

        /** @var SalesOrder $salesOrder */
        $salesOrder = $stockMove->source;

        // Handle potentially multiple product lines in the stock move
        $linesToProcess = $stockMove->productLines->count() > 0
            ? $stockMove->productLines
            : collect([$stockMove]); // Fallback for single product structure

        foreach ($linesToProcess as $lineItem) {
            // Find matching Sales Order Line
            $salesOrderLine = $salesOrder->lines()
                ->where('product_id', $lineItem->product_id)
                ->first();

            if ($salesOrderLine) {
                $salesOrderLine->updateDeliveredQuantity($salesOrderLine->quantity_delivered + $lineItem->quantity);
            }
        }
    }
}
