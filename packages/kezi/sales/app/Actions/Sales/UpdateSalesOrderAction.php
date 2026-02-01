<?php

namespace Kezi\Sales\Actions\Sales;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Kezi\Sales\DataTransferObjects\Sales\UpdateSalesOrderDTO;
use Kezi\Sales\Models\SalesOrder;
use Kezi\Sales\Models\SalesOrderLine;

/**
 * Action for updating an existing Sales Order
 */
class UpdateSalesOrderAction
{
    public function __construct(
        protected \Kezi\Accounting\Services\Accounting\LockDateService $lockDateService,
    ) {}

    /**
     * Execute the action to update a sales order
     */
    public function execute(UpdateSalesOrderDTO $dto): SalesOrder
    {
        $salesOrder = $dto->salesOrder;

        // Ensure the SO can be edited
        if (! $salesOrder->status->canBeEdited()) {
            throw new \Kezi\Foundation\Exceptions\UpdateNotAllowedException(
                'This sales order cannot be edited in its current status.'
            );
        }

        $this->lockDateService->enforce(
            $salesOrder->company,
            Carbon::parse($dto->so_date)
        );

        return DB::transaction(function () use ($dto, $salesOrder) {
            // Update the sales order header
            $salesOrder->update([
                'customer_id' => $dto->customer_id,
                'currency_id' => $dto->currency_id,
                'reference' => $dto->reference,
                'so_date' => $dto->so_date,
                'expected_delivery_date' => $dto->expected_delivery_date,
                'exchange_rate_at_creation' => $dto->exchange_rate_at_creation,
                'notes' => $dto->notes,
                'terms_and_conditions' => $dto->terms_and_conditions,
                'delivery_location_id' => $dto->delivery_location_id,
                'incoterm' => $dto->incoterm ?? $salesOrder->incoterm,
                'status' => $dto->status ?? $salesOrder->status,
            ]);

            // Delete existing lines
            $salesOrder->lines()->delete();

            // Create new lines from DTO
            $lines = [];
            foreach ($dto->lines as $lineDto) {
                $line = new SalesOrderLine([
                    'sales_order_id' => $salesOrder->id,
                    'product_id' => $lineDto->product_id,
                    'tax_id' => $lineDto->tax_id,
                    'description' => $lineDto->description,
                    'quantity' => $lineDto->quantity,
                    'quantity_delivered' => 0,
                    'quantity_invoiced' => 0,
                    'unit_price' => $lineDto->unit_price,
                    'subtotal' => Money::of(0, $salesOrder->currency->code),
                    'total_line_tax' => Money::of(0, $salesOrder->currency->code),
                    'total' => Money::of(0, $salesOrder->currency->code),
                    'expected_delivery_date' => $lineDto->expected_delivery_date,
                    'notes' => $lineDto->notes,
                ]);

                $lines[] = $line;
            }

            // Set relation and calculate totals
            $salesOrder->setRelation('lines', collect($lines));
            $salesOrder->calculateTotals();
            $salesOrder->save();

            // Save all lines
            foreach ($lines as $line) {
                $line->sales_order_id = $salesOrder->id;
                // Load the tax relationship if needed for calculation
                if ($line->tax_id) {
                    $line->load('tax');
                }
                $line->calculateTotals();
                $line->save();
            }

            return $salesOrder;
        });
    }
}
