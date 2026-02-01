<?php

namespace Kezi\Purchase\DataTransferObjects\Purchases;

use Brick\Money\Money;

readonly class ShippingCostValidationResult
{
    /**
     * @param  string[]  $warnings
     * @param  array<string, Money>  $unexpectedCosts
     */
    public function __construct(
        public bool $hasWarnings,
        public array $warnings,
        public array $unexpectedCosts,
        public Money $totalShippingCosts,
    ) {}

    public function isValid(): bool
    {
        return empty($this->warnings) && empty($this->unexpectedCosts);
    }
}
