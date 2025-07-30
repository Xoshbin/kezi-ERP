<?php

namespace App\Actions\Adjustments;

use App\DataTransferObjects\Adjustments\CreateAdjustmentDocumentDTO;
use App\Models\AdjustmentDocument;
use App\Models\Currency;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;

class CreateAdjustmentDocumentAction
{
    public function execute(CreateAdjustmentDocumentDTO $dto): AdjustmentDocument
    {
        return DB::transaction(function () use ($dto) {
            $currencyCode = Currency::find($dto->currency_id)->code;

            return AdjustmentDocument::create([
                'company_id' => $dto->company_id,
                'type' => $dto->type,
                'date' => $dto->date,
                'reference_number' => $dto->reference_number,
                'reason' => $dto->reason,
                'currency_id' => $dto->currency_id,
                'original_invoice_id' => $dto->original_invoice_id,
                'original_vendor_bill_id' => $dto->original_vendor_bill_id,
                'total_amount' => Money::of($dto->total_amount, $currencyCode),
                'total_tax' => Money::of($dto->total_tax, $currencyCode),
                'status' => AdjustmentDocument::STATUS_DRAFT, // Always start as a draft
            ]);
        });
    }
}
