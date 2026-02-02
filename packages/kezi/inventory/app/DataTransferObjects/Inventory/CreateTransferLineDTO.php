<?php

namespace Kezi\Inventory\DataTransferObjects\Inventory;

/**
 * DTO for a single line item in a transfer order.
 */
readonly class CreateTransferLineDTO
{
    public function __construct(
        public int $product_id,
        public float $quantity,
        public ?int $lot_id = null,
        public ?int $serial_number_id = null,
        public ?string $description = null,
    ) {}
}
