<?php

namespace Modules\Sales\DataTransferObjects\Sales;

use Brick\Money\Money;
use Carbon\Carbon;

/**
 * Data Transfer Object for creating a new Sales Order Line
 */
readonly class CreateSalesOrderLineDTO
{
    /**
     * @param int $product_id
     * @param string $description
     * @param float $quantity
     * @param Money $unit_price
     * @param int|null $tax_id
     * @param Carbon|null $expected_delivery_date
     * @param string|null $notes
     */
    public function __construct(
        public int $product_id,
        public string $description,
        public float $quantity,
        public Money $unit_price,
        public ?int $tax_id = null,
        public ?Carbon $expected_delivery_date = null,
        public ?string $notes = null,
    ) {
    }
}
