<?php

namespace Kezi\Foundation\Services;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use InvalidArgumentException;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\CurrencyRate;

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
     * @param  Money  $amount  The amount to convert
     * @param  Currency  $toCurrency  The target currency
     * @param  Carbon|string  $date  The date for which to use the exchange rate
     * @param  Company  $company  The company context for base currency
     * @return Money The converted amount
     *
     * @throws InvalidArgumentException If conversion is not possible
     */
    public function convert(Money $amount, Currency $toCurrency, $date, Company $company): Money
    {
        $fromCurrency = Currency::where('code', $amount->getCurrency()->getCurrencyCode())->first();

        if (! $fromCurrency) {
            throw new InvalidArgumentException("Currency {$amount->getCurrency()->getCurrencyCode()} not found");
        }

        // If same currency, return the original amount
        if ($fromCurrency->id === $toCurrency->id) {
            return $amount;
        }

        // Get the company's base currency
        $baseCurrency = $company->currency;

        // Convert to base currency first, then to target currency
        $amountInBaseCurrency = $this->convertToBaseCurrency($amount, $fromCurrency, $baseCurrency, $date, $company);

        if ($toCurrency->id === $baseCurrency->id) {
            return $amountInBaseCurrency;
        }

        return $this->convertFromBaseCurrency($amountInBaseCurrency, $toCurrency, $date, $company);
    }

    /**
     * Convert an amount to the company's base currency.
     *
     * @param  Carbon|string  $date
     *
     * @throws InvalidArgumentException
     */
    public function convertToBaseCurrency(Money $amount, Currency $fromCurrency, Currency $baseCurrency, $date, Company $company): Money
    {
        if ($fromCurrency->id === $baseCurrency->id) {
            return $amount;
        }

        $rate = $this->getExchangeRate($fromCurrency, $date, $company);

        // If no exchange rate found for the specific date, try latest available rate
        if ($rate === null) {
            $rate = $this->getLatestExchangeRate($fromCurrency, $company);
        }

        // If still no rate found, throw exception
        if ($rate === null) {
            throw new InvalidArgumentException("No exchange rate found for {$fromCurrency->code} on {$date}");
        }

        // Convert using the rate (rate represents how much base currency = 1 foreign currency)
        $convertedAmount = $amount->getAmount()->toBigDecimal()->multipliedBy($rate);

        // Create Money object using the target currency's decimal precision
        return Money::of($convertedAmount, $baseCurrency->code, null, \Brick\Math\RoundingMode::HALF_UP);
    }

    /**
     * Convert an amount from the company's base currency to another currency.
     *
     * @param  Carbon|string  $date
     *
     * @throws InvalidArgumentException
     */
    public function convertFromBaseCurrency(Money $amount, Currency $toCurrency, $date, Company $company): Money
    {
        $rate = $this->getExchangeRate($toCurrency, $date, $company);

        // If no exchange rate found for the specific date, try latest available rate
        if ($rate === null) {
            $rate = $this->getLatestExchangeRate($toCurrency, $company);
        }

        // If still no rate found, throw exception
        if ($rate === null) {
            throw new InvalidArgumentException("No exchange rate found for {$toCurrency->code} on {$date}");
        }

        // Convert from base currency (divide by rate)
        $convertedAmount = $amount->getAmount()->toBigDecimal()->dividedBy($rate, $toCurrency->decimal_places + 6, \Brick\Math\RoundingMode::HALF_UP);

        // Create Money object using the target currency's decimal precision
        return Money::of($convertedAmount, $toCurrency->code, null, \Brick\Math\RoundingMode::HALF_UP);
    }

    /**
     * Get the exchange rate for a currency on a specific date for a specific company.
     * The rate represents how much of the base currency equals 1 unit of the foreign currency.
     *
     * @param  Carbon|string  $date
     */
    public function getExchangeRate(Currency $currency, $date, Company $company): ?float
    {
        return CurrencyRate::getRateForDate($currency->id, $date, $company->id);
    }

    /**
     * Get the latest exchange rate for a currency for a specific company.
     */
    public function getLatestExchangeRate(Currency $currency, Company $company): ?float
    {
        return CurrencyRate::getLatestRate($currency->id, $company->id);
    }

    /**
     * Convert an amount using a specific exchange rate.
     * This method is useful when you already have the rate and don't need to look it up.
     *
     * @param  bool  $isFromBaseCurrency  Whether converting from base currency (divide) or to base currency (multiply)
     * @param  bool  $isFromBaseCurrency  Whether converting from base currency (divide) or to base currency (multiply)
     */
    public function convertWithRate(Money $amount, float $rate, string $toCurrencyCode, bool $isFromBaseCurrency = false): Money
    {
        if ($isFromBaseCurrency) {
            // Converting from base currency to foreign currency (divide by rate)
            // Use rounding mode for division to avoid infinite recursion on repeating decimals
            $convertedAmount = $amount->getAmount()->toBigDecimal()->dividedBy($rate, $amount->getCurrency()->getDefaultFractionDigits() + 6, \Brick\Math\RoundingMode::HALF_UP);
        } else {
            // Converting from foreign currency to base currency (multiply by rate)
            $convertedAmount = $amount->getAmount()->toBigDecimal()->multipliedBy($rate);
        }

        return Money::of($convertedAmount, $toCurrencyCode, null, \Brick\Math\RoundingMode::HALF_UP);
    }

    /**
     * Calculate the exchange difference between two amounts in different currencies
     * when they should represent the same value.
     *
     * @param  Money  $originalAmount  Original amount in foreign currency
     * @param  Money  $currentAmount  Current amount in base currency
     * @param  float  $originalRate  The rate used for the original conversion
     * @param  float  $currentRate  The current rate
     * @param  string  $baseCurrencyCode  The base currency code
     * @return Money The exchange difference (positive for gain, negative for loss)
     */
    public function calculateExchangeDifference(
        Money $originalAmount,
        Money $currentAmount,
        float $originalRate,
        float $currentRate,
        string $baseCurrencyCode,
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
