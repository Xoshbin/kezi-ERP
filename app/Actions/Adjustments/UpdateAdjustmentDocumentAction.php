<?php

namespace App\Actions\Adjustments;

use App\DataTransferObjects\Adjustments\UpdateAdjustmentDocumentDTO;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\AdjustmentDocument;
use App\Models\Currency;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;

class UpdateAdjustmentDocumentAction
{
    public function execute(UpdateAdjustmentDocumentDTO $dto): AdjustmentDocument
    {
        $adjustmentDocument = $dto->adjustmentDocument;

        if ($adjustmentDocument->status !== AdjustmentDocument::STATUS_DRAFT) {
            throw new UpdateNotAllowedException('Cannot modify a posted adjustment document.');
        }

        return DB::transaction(function () use ($dto, $adjustmentDocument) {
            $currencyCode = Currency::find($dto->currency_id)->code;

            $adjustmentDocument->update([
                'type' => $dto->type,
                'date' => $dto->date,
                'reference_number' => $dto->reference_number,
                'reason' => $dto->reason,
                'currency_id' => $dto->currency_id,
                'original_invoice_id' => $dto->original_invoice_id,
                'original_vendor_bill_id' => $dto->original_vendor_bill_id,
                'total_amount' => Money::of($dto->total_amount, $currencyCode),
                'total_tax' => Money::of($dto->total_tax, $currencyCode),
            ]);

            return $adjustmentDocument;
        });
    }
}
