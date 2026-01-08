<?php

namespace Modules\Purchase\Actions\Purchases;

use Brick\Money\Money;
use Modules\Purchase\DataTransferObjects\Purchases\CreatePurchaseOrderLineDTO;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;

/**
 * Action for creating a Purchase Order Line
 */
class CreatePurchaseOrderLineAction
{
    /**
     * Execute the action to create a purchase order line
     */
    public function execute(PurchaseOrder $purchaseOrder, CreatePurchaseOrderLineDTO $dto): PurchaseOrderLine
    {
        $currency = $purchaseOrder->currency;

        // Create the line
        $line = PurchaseOrderLine::create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $dto->product_id,
            'tax_id' => $dto->tax_id,
            'description' => $dto->description,
            'quantity' => $dto->quantity,
            'quantity_received' => 0,
            'unit_price' => $dto->unit_price,
            'subtotal' => Money::of(0, $currency->code),
            'total_line_tax' => Money::of(0, $currency->code),
            'total' => Money::of(0, $currency->code),
            'shipping_cost_type' => $dto->shipping_cost_type,
            'expected_delivery_date' => $dto->expected_delivery_date,
            'notes' => $dto->notes,
        ]);

        // Load the tax relationship if needed
        if ($dto->tax_id) {
            $line->load('tax');
        }

        // Calculate totals
        $line->calculateTotals();
        $line->save();

        // Update parent purchase order totals
        $this->updateParentTotals($purchaseOrder);

        return $line;
    }

    /**
     * Update the parent purchase order totals
     */
    protected function updateParentTotals(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->load('lines');
        $purchaseOrder->calculateTotalsFromLines();
        $purchaseOrder->saveQuietly();
    }
}
