<?php

namespace Kezi\Inventory\DataTransferObjects\Inventory;

/**
 * DTO for a single line item when shipping a transfer.
 */
readonly class ShipTransferLineDTO
{
    public function __construct(
        public int $product_id,
        public float $quantity_shipped,
        public ?int $lot_id = null,
        public ?int $serial_number_id = null,
    ) {}
}
