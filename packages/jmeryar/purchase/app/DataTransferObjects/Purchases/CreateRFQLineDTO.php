<?php

namespace Jmeryar\Purchase\DataTransferObjects\Purchases;

use Brick\Money\Money;
use Jmeryar\Accounting\Models\Tax;
use Jmeryar\Product\Models\Product;

readonly class CreateRFQLineDTO
{
    public function __construct(
        public string $description,
        public float $quantity,
        public ?Product $product = null,
        public ?Tax $tax = null,
        public ?string $unit = null,
        public ?Money $unitPrice = null,
    ) {}
}
