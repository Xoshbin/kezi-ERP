<?php

namespace App\DataTransferObjects\Sales;

use Brick\Money\Money;

readonly class RecurringInvoiceLineDTO
{
    public function __construct(
        public string $description,
        public float $quantity,
        public Money $unit_price,
        public ?int $product_id = null,
        public ?int $tax_id = null,
    ) {}
}
