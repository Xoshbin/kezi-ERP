<?php

namespace Jmeryar\Sales\DataTransferObjects\Sales;

use Brick\Money\Money;

/**
 * Data Transfer Object for creating a new Quote Line
 */
readonly class CreateQuoteLineDTO
{
    public function __construct(
        public string $description,
        public float $quantity,
        public Money $unitPrice,
        public ?int $productId = null,
        public ?int $taxId = null,
        public ?int $incomeAccountId = null,
        public ?string $unit = null,
        public float $discountPercentage = 0.0,
        public ?Money $discountAmount = null,
        public int $lineOrder = 0,
    ) {}
}
