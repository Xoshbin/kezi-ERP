<?php

namespace Kezi\Purchase\Actions\Purchases;

use Illuminate\Support\Facades\DB;
use Kezi\Purchase\DataTransferObjects\Purchases\ConvertRFQToPurchaseOrderDTO;
use Kezi\Purchase\DataTransferObjects\Purchases\CreatePurchaseOrderDTO;
use Kezi\Purchase\DataTransferObjects\Purchases\CreatePurchaseOrderLineDTO;
use Kezi\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Kezi\Purchase\Models\PurchaseOrder;
use Kezi\Purchase\Models\RequestForQuotation;

class ConvertRFQToPurchaseOrderAction
{
    public function __construct(
        protected CreatePurchaseOrderAction $createPurchaseOrderAction,
    ) {}

    public function execute(ConvertRFQToPurchaseOrderDTO $dto): PurchaseOrder
    {
        return DB::transaction(function () use ($dto) {
            $rfq = RequestForQuotation::findOrFail($dto->rfqId);

            if (! in_array($rfq->status, [RequestForQuotationStatus::BidReceived, RequestForQuotationStatus::Accepted])) {
                throw new \Exception("RFQ must be in 'Bid Received' or 'Accepted' status to convert.");
            }

            if ($rfq->converted_to_purchase_order_id) {
                throw new \Exception('RFQ is already converted to a Purchase Order.');
            }

            // Prepare PO DTO
            $poLines = $rfq->lines->map(fn ($line) => new CreatePurchaseOrderLineDTO(
                product_id: $line->product_id,
                description: $line->description,
                quantity: (float) $line->quantity,
                unit_price: $line->unit_price,
                tax_id: $line->tax_id,
            ))->all();

            $poDTO = new CreatePurchaseOrderDTO(
                company_id: $rfq->company_id,
                vendor_id: $rfq->vendor_id,
                currency_id: $rfq->currency_id,
                created_by_user_id: $dto->convertedByUserId ?? auth()->id(),
                reference: $dto->reference,
                po_date: $dto->poDate,
                expected_delivery_date: $dto->expectedDeliveryDate,
                exchange_rate_at_creation: $rfq->exchange_rate,
                notes: $dto->notes ?? $rfq->notes,
                lines: $poLines,
            );

            // Create PO
            $purchaseOrder = $this->createPurchaseOrderAction->execute($poDTO);

            // Update RFQ
            $rfq->update([
                'status' => RequestForQuotationStatus::Accepted,
                'converted_to_purchase_order_id' => $purchaseOrder->id,
                'converted_at' => now(),
            ]);

            \Kezi\Purchase\Events\RequestForQuotationAccepted::dispatch($rfq);

            return $purchaseOrder;
        });
    }
}
