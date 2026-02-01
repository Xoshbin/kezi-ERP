<?php

namespace Kezi\Purchase\DataTransferObjects\Purchases;

use Brick\Money\Money;

class CreateDebitNoteLineDTO
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
