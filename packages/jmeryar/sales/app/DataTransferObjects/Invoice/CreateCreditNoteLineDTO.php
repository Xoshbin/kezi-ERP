<?php

namespace Jmeryar\Sales\DataTransferObjects\Invoice;

use Brick\Money\Money;

class CreateCreditNoteLineDTO
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
