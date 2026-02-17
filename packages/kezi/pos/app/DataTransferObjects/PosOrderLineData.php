<?php

namespace Kezi\Pos\DataTransferObjects;

class PosOrderLineData
{
    public function __construct(
        public int $product_id,
        public float $quantity,
        public string $unit_price, // Stringified integer
        public string $tax_amount, // Stringified integer
        public string $total_amount, // Stringified integer
        public array $metadata = [],
    ) {}

    public static function from(array $data): self
    {
        return new self(
            product_id: $data['product_id'],
            quantity: $data['quantity'],
            unit_price: (string) $data['unit_price'],
            tax_amount: (string) $data['tax_amount'],
            total_amount: (string) $data['total_amount'],
            metadata: $data['metadata'] ?? [],
        );
    }
}
