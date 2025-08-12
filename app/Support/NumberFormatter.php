<?php

namespace App\Support;

use Brick\Money\Money;
use Illuminate\Support\Number;

/**
 * NumberFormatter
 *
 * Provides consistent number and currency formatting throughout the application
 * using Laravel's built-in Number utility class with configurable locale settings.
 * This allows number formatting to be independent from the application locale.
 */
class NumberFormatter
{
    /**
     * Get the locale to use for number formatting.
     *
     * @return string
     */
    public static function getNumberLocale(): string
    {
        $locale = config('formatting.number_locale', 'en');

        if ($locale === 'auto') {
            return app()->getLocale();
        }

        return $locale;
    }

    /**
     * Get the locale to use for currency formatting.
     *
     * @return string
     */
    public static function getCurrencyLocale(): string
    {
        $locale = config('formatting.currency_locale', 'en');

        if ($locale === 'auto') {
            return app()->getLocale();
        }

        return $locale;
    }

    /**
     * Format a Money object for display using Laravel's Number utility.
     *
     * @param Money $money
     * @return string
     */
    public static function formatMoney(Money $money): string
    {
        $locale = self::getCurrencyLocale();

        return Number::currency(
            $money->getAmount()->toFloat(),
            in: $money->getCurrency()->getCurrencyCode(),
            locale: $locale
        );
    }

    /**
     * Format a Money object using Brick\Money's formatTo method with configured locale.
     *
     * @param Money $money
     * @return string
     */
    public static function formatMoneyTo(Money $money): string
    {
        return $money->formatTo(self::getCurrencyLocale());
    }

    /**
     * Format a number for display using Laravel's Number utility.
     *
     * @param float|int $number
     * @param int|null $precision
     * @return string
     */
    public static function formatNumber($number, ?int $precision = null): string
    {
        $locale = self::getNumberLocale();

        return Number::format($number, precision: $precision, locale: $locale);
    }

    /**
     * Format a percentage using Laravel's Number utility.
     *
     * @param float|int $percentage
     * @param int $precision
     * @return string
     */
    public static function formatPercentage($percentage, int $precision = 1): string
    {
        $locale = self::getNumberLocale();

        return Number::percentage($percentage, precision: $precision, locale: $locale);
    }
}
