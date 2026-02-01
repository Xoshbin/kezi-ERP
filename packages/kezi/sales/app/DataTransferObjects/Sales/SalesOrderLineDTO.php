<?php

namespace Kezi\Sales\DataTransferObjects\Sales;

use Brick\Money\Money;
use Carbon\Carbon;

/**
 * Data Transfer Object for a Sales Order Line
 *
 * Used for both create and update operations.
 */
readonly class SalesOrderLineDTO
{
    public function __construct(
        public int $product_id,
        public string $description,
        public float $quantity,
        public Money $unit_price,
        public ?int $tax_id = null,
        public ?Carbon $expected_delivery_date = null,
        public ?string $notes = null,
    ) {}
}
