<?php

namespace Kezi\Inventory\DataTransferObjects\Adjustments;

use Brick\Money\Money;

class CreateAdjustmentDocumentLineDTO
{
    public function __construct(
        public readonly string $description,
        public readonly float $quantity,
        public readonly Money $unit_price,
        public readonly int $account_id,
        public readonly ?int $product_id,
        public readonly ?int $tax_id,
    ) {}
}
