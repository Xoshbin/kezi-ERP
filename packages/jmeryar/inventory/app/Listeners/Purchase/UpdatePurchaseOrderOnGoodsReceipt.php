<?php

namespace Jmeryar\Inventory\Listeners\Purchase;

use Jmeryar\Inventory\Events\GoodsReceiptValidated;
use Jmeryar\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Jmeryar\Purchase\Models\PurchaseOrderLine;

/**
 * Updates the Purchase Order when a Goods Receipt is validated.
 *
 * This listener:
 * - Updates PurchaseOrderLine.quantity_received for each received line
 * - Updates the PurchaseOrder status based on receiving progress
 */
class UpdatePurchaseOrderOnGoodsReceipt
{
    /**
     * Handle the GoodsReceiptValidated event.
     */
    public function handle(GoodsReceiptValidated $event): void
    {
        $picking = $event->stockPicking;

        if (! $picking->isLinkedToPurchaseOrder()) {
            return; // Not linked to a PO, nothing to update
        }

        $purchaseOrder = $picking->purchaseOrder;

        if (! $purchaseOrder) {
            return;
        }

        // Update quantity_received for each line
        foreach ($event->receivedLines as $receivedLine) {
            if (! isset($receivedLine['purchase_order_line_id'])) {
                continue;
            }

            $poLine = PurchaseOrderLine::find($receivedLine['purchase_order_line_id']);
            if ($poLine) {
                // Note: quantity_received was already updated in ValidateGoodsReceiptAction
                // This is just a safety check / alternative path
                $poLine->refresh();
            }
        }

        // Refresh the PO lines
        $purchaseOrder->refresh();
        $purchaseOrder->load('lines');

        // Update PO status based on receiving progress
        $this->updatePurchaseOrderStatus($purchaseOrder);
    }

    /**
     * Update the Purchase Order status based on received quantities.
     */
    private function updatePurchaseOrderStatus(\Jmeryar\Purchase\Models\PurchaseOrder $purchaseOrder): void
    {
        // Don't update if the order is cancelled or done
        if (
            $purchaseOrder->status === PurchaseOrderStatus::Cancelled ||
            $purchaseOrder->status === PurchaseOrderStatus::Done
        ) {
            return;
        }

        if ($purchaseOrder->isFullyReceived()) {
            // All goods received, move to billing phase
            $purchaseOrder->status = PurchaseOrderStatus::ToBill;
        } elseif ($purchaseOrder->isPartiallyReceived()) {
            // Some goods received
            $purchaseOrder->status = PurchaseOrderStatus::PartiallyReceived;
        }
        // If nothing received yet, keep current status

        $purchaseOrder->save();
    }
}
