<?php

namespace Jmeryar\Manufacturing\DataTransferObjects;

use Brick\Money\Money;

readonly class BOMLineDTO
{
    public function __construct(
        public int $productId,
        public float $quantity,
        public Money $unitCost,
        public ?int $workCenterId = null,
    ) {}
}
