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

        $line->unit_price = $dto->unitPrice ?? \Brick\Money\Money::of(0, $currencyCode);
        $line->subtotal = \Brick\Money\Money::of(0, $currencyCode);
        $line->tax_amount = \Brick\Money\Money::of(0, $currencyCode);
        $line->total = \Brick\Money\Money::of(0, $currencyCode);

        $line->save();

        return $line;
    }
}
