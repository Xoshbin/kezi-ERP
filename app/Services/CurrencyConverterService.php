<?php

namespace App\Services;

use App\DataTransferObjects\CurrencyConversionResult;
use App\Models\Company;
use App\Models\Currency;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

class CurrencyConverterService
{
    /**
     * Get the exchange rate between two currencies.
     */
    public function getExchangeRate(Currency $fromCurrency, Currency $toCurrency): float
    {
        // If same currency, no conversion needed
        if ($fromCurrency->id === $toCurrency->id) {
            return 1.0;
        }

        // Use the exchange rate from the source currency
        // This assumes all exchange rates are relative to a base currency
        return $fromCurrency->exchange_rate;
    }

    /**
     * Convert a Money amount from one currency to another.
     */
    public function convertAmount(
        Money $amount,
        Currency $targetCurrency,
        ?float $exchangeRate = null
    ): Money {
        $sourceCurrency = Currency::where('code', $amount->getCurrency()->getCurrencyCode())->first();

        // If source currency doesn't exist in database, we can't convert
        if (!$sourceCurrency) {
            throw new \InvalidArgumentException(
                "Source currency '{$amount->getCurrency()->getCurrencyCode()}' not found in database"
            );
        }

        // Calculate exchange rate if not provided
        if ($exchangeRate === null) {
            $exchangeRate = $this->getExchangeRate($sourceCurrency, $targetCurrency);
        }

        // If no conversion needed, return original amount
        if ($exchangeRate === 1.0) {
            return $amount;
        }

        // Convert the amount
        return Money::of(
            $amount->getAmount()->multipliedBy($exchangeRate),
            $targetCurrency->code,
            null,
            RoundingMode::HALF_UP
        );
    }

    /**
     * Convert an amount to the company's base currency with full tracking information.
     */
    public function convertToCompanyBaseCurrency(
        Money $originalAmount,
        Currency $originalCurrency,
        Company $company
    ): CurrencyConversionResult {
        $baseCurrency = $company->currency;
        $exchangeRate = $this->getExchangeRate($originalCurrency, $baseCurrency);

        $convertedAmount = $this->convertAmount($originalAmount, $baseCurrency, $exchangeRate);

        return new CurrencyConversionResult(
            originalAmount: $originalAmount,
            convertedAmount: $convertedAmount,
            originalCurrency: $originalCurrency,
            targetCurrency: $baseCurrency,
            exchangeRate: $exchangeRate
        );
    }

    /**
     * Convert multiple amounts to company base currency.
     * Useful for transactions with multiple line items.
     */
    public function convertMultipleToCompanyBaseCurrency(
        array $amounts,
        Currency $originalCurrency,
        Company $company
    ): array {
        $results = [];

        foreach ($amounts as $key => $amount) {
            $results[$key] = $this->convertToCompanyBaseCurrency(
                $amount,
                $originalCurrency,
                $company
            );
        }

        return $results;
    }

    /**
     * Check if two currencies are the same (no conversion needed).
     */
    public function isSameCurrency(Currency $currency1, Currency $currency2): bool
    {
        return $currency1->id === $currency2->id;
    }

    /**
     * Check if an amount needs conversion to company base currency.
     */
    public function needsConversionToBaseCurrency(Currency $originalCurrency, Company $company): bool
    {
        return !$this->isSameCurrency($originalCurrency, $company->currency);
    }

    /**
     * Create zero amount in target currency.
     */
    public function createZeroAmount(Currency $currency): Money
    {
        return Money::zero($currency->code);
    }

    /**
     * Validate that currencies are compatible for conversion.
     *
     * @throws \InvalidArgumentException
     */
    public function validateCurrenciesForConversion(Currency $fromCurrency, Currency $toCurrency): void
    {
        if (!$fromCurrency->is_active) {
            throw new \InvalidArgumentException("Source currency {$fromCurrency->code} is not active");
        }

        if (!$toCurrency->is_active) {
            throw new \InvalidArgumentException("Target currency {$toCurrency->code} is not active");
        }

        if ($fromCurrency->exchange_rate <= 0) {
            throw new \InvalidArgumentException("Source currency {$fromCurrency->code} has invalid exchange rate");
        }
    }
}
