<?php

namespace Kezi\Sales\DataTransferObjects\Sales;

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
        public readonly ?string $deferred_start_date = null,
        public readonly ?string $deferred_end_date = null,
    ) {}
}
