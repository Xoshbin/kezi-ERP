<?php

namespace Kezi\Purchase\Actions\Purchases;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Kezi\Purchase\DataTransferObjects\Purchases\UpdatePurchaseOrderDTO;
use Kezi\Purchase\Models\PurchaseOrder;
use Kezi\Purchase\Models\PurchaseOrderLine;

/**
 * Action for updating an existing Purchase Order
 */
class UpdatePurchaseOrderAction
{
    public function __construct(
        protected \Kezi\Accounting\Services\Accounting\LockDateService $lockDateService,
    ) {}

    /**
     * Execute the action to update a purchase order
     */
    public function execute(UpdatePurchaseOrderDTO $dto): PurchaseOrder
    {
        $purchaseOrder = $dto->purchaseOrder;

        // Ensure the PO can be edited
        if (! $purchaseOrder->canBeEdited()) {
            throw new \Kezi\Foundation\Exceptions\UpdateNotAllowedException(
                'This purchase order cannot be edited in its current status.'
            );
        }

        $this->lockDateService->enforce(
            $purchaseOrder->company,
            Carbon::parse($dto->po_date)
        );

        return DB::transaction(function () use ($dto, $purchaseOrder) {
            // Update the purchase order header
            $purchaseOrder->update([
                'vendor_id' => $dto->vendor_id,
                'currency_id' => $dto->currency_id,
                'reference' => $dto->reference,
                'po_date' => $dto->po_date,
                'expected_delivery_date' => $dto->expected_delivery_date,
                'exchange_rate_at_creation' => $dto->exchange_rate_at_creation,
                'notes' => $dto->notes,
                'terms_and_conditions' => $dto->terms_and_conditions,
                'delivery_location_id' => $dto->delivery_location_id,
                'incoterm' => $dto->incoterm ?? $purchaseOrder->incoterm,
                'status' => $dto->status ?? $purchaseOrder->status,
            ]);

            // Delete existing lines
            $purchaseOrder->lines()->delete();

            // Create new lines and calculate their totals
            $lines = [];
            foreach ($dto->lines as $lineDto) {
                $line = new PurchaseOrderLine([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $lineDto->product_id,
                    'tax_id' => $lineDto->tax_id,
                    'description' => $lineDto->description,
                    'quantity' => $lineDto->quantity,
                    'quantity_received' => 0,
                    'unit_price' => $lineDto->unit_price,
                    'expected_delivery_date' => $lineDto->expected_delivery_date,
                    'shipping_cost_type' => $lineDto->shipping_cost_type,
                    'notes' => $lineDto->notes,
                ]);

                // We must associate the PO to the line for DocumentCurrencyMoneyCast to work
                $line->setRelation('purchaseOrder', $purchaseOrder);

                if ($line->tax_id) {
                    $line->load('tax');
                }
                $line->calculateTotals();
                $lines[] = $line;
            }

            // Set relation and calculate PO totals
            $purchaseOrder->setRelation('lines', collect($lines));
            $purchaseOrder->calculateTotalsFromLines();
            $purchaseOrder->save();

            // Save all lines
            foreach ($lines as $line) {
                $line->purchase_order_id = $purchaseOrder->id;
                $line->save();
            }

            return $purchaseOrder;
        });
    }
}
