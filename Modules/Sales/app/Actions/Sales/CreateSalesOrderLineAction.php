<?php

namespace Modules\Sales\Actions\Sales;

use Modules\Sales\DataTransferObjects\Sales\CreateSalesOrderLineDTO;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;

/**
 * Action for creating a new Sales Order Line
 */
class CreateSalesOrderLineAction
{
    /**
     * Execute the action to create a sales order line
     */
    public function execute(SalesOrder $salesOrder, CreateSalesOrderLineDTO $dto): SalesOrderLine
    {
        $line = new SalesOrderLine();
        $line->sales_order_id = $salesOrder->id;
        $line->fill([
            'product_id' => $dto->product_id,
            'tax_id' => $dto->tax_id,
            'description' => $dto->description,
            'quantity' => $dto->quantity,
            'quantity_delivered' => 0,
            'quantity_invoiced' => 0,
            // 'unit_price' => $dto->unit_price, // Defer assignment
            'expected_delivery_date' => $dto->expected_delivery_date,
            'notes' => $dto->notes,
            'subtotal' => 0, // Will be calculated
            'total_line_tax' => 0, // Will be calculated
            'total' => 0, // Will be calculated
        ]);

        // Explicitly set unit_price after context is established
        $line->unit_price = $dto->unit_price;

        // Calculate totals for this line
        $line->calculateTotals();
        $line->save();

        return $line;
    }
}
