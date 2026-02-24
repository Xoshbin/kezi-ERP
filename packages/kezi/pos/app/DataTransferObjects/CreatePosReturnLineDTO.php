<?php

namespace Kezi\Pos\DataTransferObjects;

readonly class CreatePosReturnLineDTO
{
    public function __construct(
        public int $original_order_line_id,
        public int $product_id,
        public float $quantity_returned,
        public float $quantity_available,
        public int $unit_price,
        public int $refund_amount,
        public int $restocking_fee_line,
        public bool $restock,
        public ?string $item_condition,
        public ?string $return_reason_line,
        public ?array $metadata,
    ) {}
}
