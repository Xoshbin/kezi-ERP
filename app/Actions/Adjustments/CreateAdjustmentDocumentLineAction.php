<?php

namespace App\Actions\Adjustments;

use App\DataTransferObjects\Adjustments\CreateAdjustmentDocumentLineDTO;
use App\Models\AdjustmentDocument;
use App\Models\AdjustmentDocumentLine;
use App\Models\Tax;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

class CreateAdjustmentDocumentLineAction
{
    public function execute(AdjustmentDocument $adjustmentDocument, CreateAdjustmentDocumentLineDTO $dto): AdjustmentDocumentLine
    {
        $currency = $adjustmentDocument->currency;

        // 1. Explicitly create the Money object from the DTO.
        $unitPrice = $dto->unit_price instanceof Money
            ? $dto->unit_price
            : Money::of($dto->unit_price, $currency->code);

        // 2. Perform calculations with full context.
        $subtotal = $unitPrice->multipliedBy($dto->quantity, RoundingMode::HALF_UP);

        $taxAmount = Money::of(0, $currency->code);
        if ($dto->tax_id) {
            $tax = Tax::find($dto->tax_id);
            if ($tax) {
                $taxRate = $tax->rate;
                $taxAmount = $subtotal->multipliedBy((string) $taxRate, RoundingMode::HALF_UP);
            }
        }

        // 3. Create the line with all calculated values.
        return AdjustmentDocumentLine::create([
            'company_id' => $adjustmentDocument->company_id,
            'adjustment_document_id' => $adjustmentDocument->id,
            'product_id' => $dto->product_id,
            'description' => $dto->description,
            'quantity' => $dto->quantity,
            'unit_price' => $unitPrice,
            'tax_id' => $dto->tax_id,
            'account_id' => $dto->account_id,
            'subtotal' => $subtotal,
            'total_line_tax' => $taxAmount,
        ]);
    }
}
