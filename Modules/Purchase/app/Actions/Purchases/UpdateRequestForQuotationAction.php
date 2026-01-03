<?php

namespace Modules\Purchase\Actions\Purchases;

use Illuminate\Support\Facades\DB;
use Modules\Purchase\DataTransferObjects\Purchases\UpdateRFQDTO;
use Modules\Purchase\Models\RequestForQuotation;
use Modules\Purchase\Models\RequestForQuotationLine;

class UpdateRequestForQuotationAction
{
    public function execute(UpdateRFQDTO $dto): RequestForQuotation
    {
        $rfq = $dto->rfq;

        // Ensure RFQ can be edited
        // Draft or specific status checking if needed

        return DB::transaction(function () use ($dto, $rfq) {
            $rfq->update([
                'vendor_id' => $dto->vendorId,
                'currency_id' => $dto->currencyId,
                'notes' => $dto->notes,
                'rfq_date' => $dto->rfqDate,
                'valid_until' => $dto->validUntil,
            ]);

            // Re-create lines to avoid complex update/cast issues
            $rfq->lines()->delete();

            $lines = [];
            foreach ($dto->lines as $lineDto) {
                // Manually create line to ensure Money objects are set respecting casts
                $line = new RequestForQuotationLine;
                $line->rfq()->associate($rfq);
                $line->rfq_id = $rfq->id; // Explicit FK

                $line->product_id = $lineDto->product?->id;
                $line->tax_id = $lineDto->tax?->id;
                $line->description = $lineDto->description;
                $line->quantity = $lineDto->quantity;
                $line->unit = $lineDto->unit;

                $currencyCode = $rfq->currency->code;

                $line->unit_price = $lineDto->unitPrice ?? \Brick\Money\Money::of(0, $currencyCode);
                $line->subtotal = \Brick\Money\Money::of(0, $currencyCode);
                $line->tax_amount = \Brick\Money\Money::of(0, $currencyCode);
                $line->total = \Brick\Money\Money::of(0, $currencyCode);

                $line->save();
                $lines[] = $line;
            }

            $rfq->setRelation('lines', collect($lines));

            // Recalculate totals service or logic
            app(\Modules\Purchase\Services\RequestForQuotationService::class)->calculateTotals($rfq);
            $rfq->save();

            return $rfq;
        });
    }
}
