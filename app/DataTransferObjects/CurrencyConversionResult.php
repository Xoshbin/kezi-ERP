<?php

namespace App\DataTransferObjects;

use App\Models\Currency;
use Brick\Money\Money;

/**
 * Data Transfer Object for currency conversion results.
 * 
 * Contains all information needed for audit trails and journal entry creation.
 */
readonly class CurrencyConversionResult
{
    public function __construct(
        public Money $originalAmount,
        public Money $convertedAmount,
        public Currency $originalCurrency,
        public Currency $targetCurrency,
        public float $exchangeRate
    ) {}

    /**
     * Check if any conversion was actually performed.
     */
    public function wasConverted(): bool
    {
        return $this->exchangeRate !== 1.0;
    }

    /**
     * Get the conversion summary as a string.
     */
    public function getSummary(): string
    {
        if (!$this->wasConverted()) {
            return "No conversion needed (same currency: {$this->originalCurrency->code})";
        }

        return sprintf(
            "Converted %s to %s at rate %.4f",
            $this->originalAmount->formatTo('en_US'),
            $this->convertedAmount->formatTo('en_US'),
            $this->exchangeRate
        );
    }

    /**
     * Create a zero amount in the target currency.
     */
    public function createZeroInTargetCurrency(): Money
    {
        return Money::zero($this->targetCurrency->code);
    }
}
