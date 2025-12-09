<?php

namespace Modules\Inventory\Actions\Adjustments;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Modules\Accounting\Models\Tax;
use Modules\Inventory\DataTransferObjects\Adjustments\CreateAdjustmentDocumentLineDTO;
use Modules\Inventory\Models\AdjustmentDocument;
use Modules\Inventory\Models\AdjustmentDocumentLine;

class CreateAdjustmentDocumentLineAction
{
    public function execute(AdjustmentDocument $adjustmentDocument, CreateAdjustmentDocumentLineDTO $dto): AdjustmentDocumentLine
    {
        $currency = $adjustmentDocument->currency;

        // 1. Use the Money object from the DTO.
        $unitPrice = $dto->unit_price;

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
