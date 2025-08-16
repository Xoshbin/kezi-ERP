<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * CurrencyConverterService
 *
 * Provides currency conversion functionality using historical exchange rates.
 * This service is essential for multi-currency accounting operations,
 * ensuring accurate conversion between currencies at specific dates.
 */
class CurrencyConverterService
{
    /**
     * Convert an amount from one currency to another using the exchange rate
     * effective on a specific date.
     *
     * @param Money $amount The amount to convert
     * @param Currency $toCurrency The target currency
     * @param Carbon|string $date The date for which to use the exchange rate
     * @param Company $company The company context for base currency
     * @return Money The converted amount
     * @throws InvalidArgumentException If conversion is not possible
     */
    public function convert(Money $amount, Currency $toCurrency, $date, Company $company): Money
    {
        $fromCurrency = Currency::where('code', $amount->getCurrency()->getCurrencyCode())->first();

        if (!$fromCurrency) {
            throw new InvalidArgumentException("Currency {$amount->getCurrency()->getCurrencyCode()} not found");
        }

        // If same currency, return the original amount
        if ($fromCurrency->id === $toCurrency->id) {
            return $amount;
        }

        // Get the company's base currency
        $baseCurrency = $company->currency;

        // Convert to base currency first, then to target currency
        $amountInBaseCurrency = $this->convertToBaseCurrency($amount, $fromCurrency, $baseCurrency, $date);

        if ($toCurrency->id === $baseCurrency->id) {
            return $amountInBaseCurrency;
        }

        return $this->convertFromBaseCurrency($amountInBaseCurrency, $toCurrency, $date);
    }

    /**
     * Convert an amount to the company's base currency.
     *
     * @param Money $amount
     * @param Currency $fromCurrency
     * @param Currency $baseCurrency
     * @param Carbon|string $date
     * @return Money
     * @throws InvalidArgumentException
     */
    public function convertToBaseCurrency(Money $amount, Currency $fromCurrency, Currency $baseCurrency, $date): Money
    {
        if ($fromCurrency->id === $baseCurrency->id) {
            return $amount;
        }

        $rate = $this->getExchangeRate($fromCurrency, $date);

        if ($rate === null) {
            throw new InvalidArgumentException("No exchange rate found for {$fromCurrency->code} on {$date}");
        }

        // Convert using the rate (rate represents how much base currency = 1 foreign currency)
        $convertedAmount = $amount->getAmount()->toFloat() * $rate;

        return Money::of($convertedAmount, $baseCurrency->code);
    }

    /**
     * Convert an amount from the company's base currency to another currency.
     *
     * @param Money $amount
     * @param Currency $toCurrency
     * @param Carbon|string $date
     * @return Money
     * @throws InvalidArgumentException
     */
    public function convertFromBaseCurrency(Money $amount, Currency $toCurrency, $date): Money
    {
        $rate = $this->getExchangeRate($toCurrency, $date);

        if ($rate === null) {
            throw new InvalidArgumentException("No exchange rate found for {$toCurrency->code} on {$date}");
        }

        // Convert from base currency (divide by rate)
        $convertedAmount = $amount->getAmount()->toFloat() / $rate;

        return Money::of($convertedAmount, $toCurrency->code);
    }

    /**
     * Get the exchange rate for a currency on a specific date.
     * The rate represents how much of the base currency equals 1 unit of the foreign currency.
     *
     * @param Currency $currency
     * @param Carbon|string $date
     * @return float|null
     */
    public function getExchangeRate(Currency $currency, $date): ?float
    {
        return CurrencyRate::getRateForDate($currency->id, $date);
    }

    /**
     * Get the latest exchange rate for a currency.
     *
     * @param Currency $currency
     * @return float|null
     */
    public function getLatestExchangeRate(Currency $currency): ?float
    {
        return CurrencyRate::getLatestRate($currency->id);
    }

    /**
     * Convert an amount using a specific exchange rate.
     * This method is useful when you already have the rate and don't need to look it up.
     *
     * @param Money $amount
     * @param float $rate
     * @param string $toCurrencyCode
     * @param bool $isFromBaseCurrency Whether converting from base currency (divide) or to base currency (multiply)
     * @return Money
     */
    public function convertWithRate(Money $amount, float $rate, string $toCurrencyCode, bool $isFromBaseCurrency = false): Money
    {
        if ($isFromBaseCurrency) {
            // Converting from base currency to foreign currency (divide by rate)
            $convertedAmount = $amount->getAmount()->toFloat() / $rate;
        } else {
            // Converting from foreign currency to base currency (multiply by rate)
            $convertedAmount = $amount->getAmount()->toFloat() * $rate;
        }

        return Money::of($convertedAmount, $toCurrencyCode);
    }

    /**
     * Calculate the exchange difference between two amounts in different currencies
     * when they should represent the same value.
     *
     * @param Money $originalAmount Original amount in foreign currency
     * @param Money $currentAmount Current amount in base currency
     * @param float $originalRate The rate used for the original conversion
     * @param float $currentRate The current rate
     * @param string $baseCurrencyCode The base currency code
     * @return Money The exchange difference (positive for gain, negative for loss)
     */
    public function calculateExchangeDifference(
        Money $originalAmount,
        Money $currentAmount,
        float $originalRate,
        float $currentRate,
        string $baseCurrencyCode
    ): Money {
        // Calculate what the original amount would be worth at current rate
        $currentValueInBaseCurrency = $this->convertWithRate(
            $originalAmount,
            $currentRate,
            $baseCurrencyCode,
            false
        );

        // The difference is the gain/loss
        return $currentValueInBaseCurrency->minus($currentAmount);
    }
}
