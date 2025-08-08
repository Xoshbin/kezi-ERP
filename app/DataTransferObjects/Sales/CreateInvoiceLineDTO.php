<?php

namespace App\DataTransferObjects\Sales;

use Brick\Money\Money;

class CreateInvoiceLineDTO
{
    public function __construct(
        public readonly string $description,
        public readonly float $quantity,
        public readonly Money $unit_price,
        public readonly int $income_account_id,
        public readonly ?int $product_id,
        public readonly ?int $tax_id,
    ) {}
}
