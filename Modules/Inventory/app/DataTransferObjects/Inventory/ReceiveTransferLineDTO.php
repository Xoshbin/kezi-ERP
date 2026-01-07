<?php

namespace Modules\Inventory\DataTransferObjects\Inventory;

/**
 * DTO for a single line item when receiving a transfer.
 */
readonly class ReceiveTransferLineDTO
{
    public function __construct(
        public int $product_id,
        public float $quantity_received,
        public ?int $lot_id = null,
        public ?int $serial_number_id = null,
    ) {}
}
