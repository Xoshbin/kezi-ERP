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
            $adjustmentDocument->update([
                'type' => $dto->type,
                'date' => $dto->date,
                'reference_number' => $dto->reference_number,
                'reason' => $dto->reason,
                'currency_id' => $dto->currency_id,
                'original_invoice_id' => $dto->original_invoice_id,
                'original_vendor_bill_id' => $dto->original_vendor_bill_id,
            ]);

            // Sync the lines: delete old ones, create new ones.
            $adjustmentDocument->lines()->delete();

            $currencyCode = Currency::find($dto->currency_id)->code;
            $linesToCreate = [];
            foreach ($dto->lines as $lineDto) {
                $linesToCreate[] = [
                    'product_id' => $lineDto->product_id,
                    'description' => $lineDto->description,
                    'quantity' => $lineDto->quantity,
                    'unit_price' => Money::of($lineDto->unit_price, $currencyCode),
                    'tax_id' => $lineDto->tax_id,
                    'account_id' => $lineDto->account_id,
                ];
            }

            if (!empty($linesToCreate)) {
                $adjustmentDocument->lines()->createMany($linesToCreate);
            }

            // The model's saving observer will recalculate totals automatically.
            $adjustmentDocument->save();

            return $adjustmentDocument;
        });
    }
}
