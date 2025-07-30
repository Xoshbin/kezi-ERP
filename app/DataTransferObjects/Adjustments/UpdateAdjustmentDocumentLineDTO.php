<?php

namespace App\DataTransferObjects\Adjustments;

class UpdateAdjustmentDocumentLineDTO
{
    public function __construct(
        public readonly string $description,
        public readonly float $quantity,
        public readonly string $unit_price,
        public readonly int $account_id,
        public readonly ?int $product_id,
        public readonly ?int $tax_id,
    ) {}
}
