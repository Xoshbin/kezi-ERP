<?php

namespace Modules\Purchase\Actions\Purchases;

use Modules\Purchase\DataTransferObjects\Purchases\CreateRFQLineDTO;
use Modules\Purchase\Models\RequestForQuotation;
use Modules\Purchase\Models\RequestForQuotationLine;

class CreateRequestForQuotationLineAction
{
    public function execute(RequestForQuotation $rfq, CreateRFQLineDTO $dto): RequestForQuotationLine
    {
        $line = new RequestForQuotationLine;
        $line->rfq()->associate($rfq);
        // Explicitly set FK to help Cast resolution if needed
        $line->rfq_id = $rfq->id;

        // Explicitly set Money objects to bypass failing cast resolution logic for numeric values
        $line->product_id = $dto->product?->id;
        $line->tax_id = $dto->tax?->id;
        $line->description = $dto->description;
        $line->quantity = $dto->quantity;
        $line->unit = $dto->unit;

        $currencyCode = $rfq->currency->code;

        $unitPrice = $dto->unitPrice ?? \Brick\Money\Money::of(0, $currencyCode);
        $subtotal = $unitPrice->multipliedBy($dto->quantity, \Brick\Math\RoundingMode::HALF_UP);

        $taxAmount = \Brick\Money\Money::of(0, $currencyCode);
        if ($dto->tax) {
            $taxRate = $dto->tax->rate / 100;
            $taxAmount = $subtotal->multipliedBy((string) $taxRate, \Brick\Math\RoundingMode::HALF_UP);
        }

        $line->unit_price = $unitPrice;
        $line->subtotal = $subtotal;
        $line->tax_amount = $taxAmount;
        $line->total = $subtotal->plus($taxAmount);

        $line->save();

        return $line;
    }
}
