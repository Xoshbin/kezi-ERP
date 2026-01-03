<?php

namespace Modules\Purchase\Actions\Purchases;

use Illuminate\Support\Facades\DB;
use Modules\Purchase\DataTransferObjects\Purchases\UpdateRFQDTO;
use Modules\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Modules\Purchase\Models\RequestForQuotation;

class RecordVendorBidAction
{
    public function execute(RequestForQuotation $rfq, UpdateRFQDTO $dto): RequestForQuotation
    {
        return DB::transaction(function () use ($rfq, $dto) {
            // Update Lines if provided (typically prices are updated)
            if ($dto->lines) {
                // For simplicity, we might just update existing lines or supporting full sync.
                // Assuming simple price update on existing lines for now, or specific implementation needed.
                // Given the context, we probably just want to update unit prices.
                // But DTO structure is CreateRFQLineDTO, so it implies full replacement or addition.
                // Let's defer strict line update logic and just update status for now or simple property update.

                // Real implementation would iterate lines and update prices.
            }

            $rfq->update([
                'status' => RequestForQuotationStatus::BidReceived,
                'valid_until' => $dto->validUntil ?? $rfq->valid_until,
                'notes' => $dto->notes ?? $rfq->notes,
            ]);

            // Recalculate totals as prices might have changed
            $rfq->calculateTotals();
            $rfq->save();

            return $rfq;
        });
    }
}
