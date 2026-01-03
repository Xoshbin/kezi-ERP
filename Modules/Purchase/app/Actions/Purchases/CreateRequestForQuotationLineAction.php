<?php

namespace Modules\Purchase\Actions\Purchases;

use Modules\Purchase\DataTransferObjects\Purchases\CreateRFQLineDTO;
use Modules\Purchase\Models\RequestForQuotation;
use Modules\Purchase\Models\RequestForQuotationLine;

class CreateRequestForQuotationLineAction
{
    public function execute(RequestForQuotation $rfq, CreateRFQLineDTO $dto): RequestForQuotationLine
    {
        return $rfq->lines()->create([
            'product_id' => $dto->product?->id,
            'tax_id' => $dto->tax?->id,
            'description' => $dto->description,
            'quantity' => $dto->quantity,
            'unit' => $dto->unit,
            'unit_price' => $dto->unitPrice ?? 0,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ]);
    }
}
