<?php

namespace Kezi\Inventory\DataTransferObjects\Inventory;

readonly class CreateStockMoveProductLineDTO
{
    public function __construct(
        public int $product_id,
        public float $quantity,
        public int $from_location_id,
        public int $to_location_id,
        public ?string $description = null,
        public ?string $source_type = null,
        public ?int $source_id = null,
    ) {}
}
