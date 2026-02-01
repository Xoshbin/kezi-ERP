<?php

namespace Kezi\Sales\DataTransferObjects\Sales;

use Brick\Money\Money;

/**
 * Data Transfer Object for updating an existing Quote Line
 */
readonly class UpdateQuoteLineDTO
{
    public function __construct(
        public ?int $lineId = null,
        public ?string $description = null,
        public ?float $quantity = null,
        public ?Money $unitPrice = null,
        public ?int $productId = null,
        public ?int $taxId = null,
        public ?int $incomeAccountId = null,
        public ?string $unit = null,
        public ?float $discountPercentage = null,
        public ?Money $discountAmount = null,
        public ?int $lineOrder = null,
        public bool $shouldDelete = false,
    ) {}
}
