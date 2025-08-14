<?php

namespace App\Casts;

use App\Models\Currency;
use Brick\Math\Exception\MathException;
use Brick\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * OriginalMoneyCast
 * 
 * A specialized Money cast that uses the original_currency_id field instead of 
 * the model's default currency. This is used for storing monetary amounts in 
 * their original transaction currency while the main debit/credit amounts are 
 * stored in the company's base currency.
 */
class OriginalMoneyCast implements CastsAttributes
{
    /**
     * Get the real value of an attribute.
     *
     * @param array<string, mixed> $attributes
     * @throws MathException
     */
    public function get($model, string $key, $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        // Use original_currency_id instead of the model's default currency
        $originalCurrencyId = $attributes['original_currency_id'] ?? null;
        
        if (!$originalCurrencyId) {
            // Fallback to model's default currency if original_currency_id is not set
            $currency = $this->resolveCurrency($model);
            return Money::ofMinor($value, $currency->code);
        }

        $originalCurrency = Currency::find($originalCurrencyId);
        if (!$originalCurrency) {
            throw new InvalidArgumentException("Original currency with ID {$originalCurrencyId} not found.");
        }

        return Money::ofMinor($value, $originalCurrency->code);
    }

    /**
     * Prepare the value for storage.
     *
     * @param array<string, mixed> $attributes
     */
    public function set($model, string $key, $value, array $attributes): ?array
    {
        if ($value === null) {
            return [$key => null];
        }

        if ($value instanceof Money) {
            return [$key => $value->getMinorAmount()->toInt()];
        }

        if (is_numeric($value)) {
            // For numeric values, we need to know which currency to use
            $originalCurrencyId = $attributes['original_currency_id'] ?? null;
            
            if (!$originalCurrencyId) {
                // Fallback to model's default currency
                $currency = $this->resolveCurrency($model);
                $money = Money::of($value, $currency->code);
                return [$key => $money->getMinorAmount()->toInt()];
            }

            $originalCurrency = Currency::find($originalCurrencyId);
            if (!$originalCurrency) {
                throw new InvalidArgumentException("Original currency with ID {$originalCurrencyId} not found.");
            }

            $money = Money::of($value, $originalCurrency->code);
            return [$key => $money->getMinorAmount()->toInt()];
        }

        throw new InvalidArgumentException('The given value is not a valid Money instance or numeric value.');
    }

    /**
     * Resolve the currency for the model (fallback method).
     */
    private function resolveCurrency($model): Currency
    {
        // Try to get currency from the model's getCurrencyIdAttribute method
        if (method_exists($model, 'getCurrencyIdAttribute')) {
            $currencyId = $model->getCurrencyIdAttribute();
            $currency = Currency::find($currencyId);
            if ($currency) {
                return $currency;
            }
        }

        // Fallback to a default currency (this should rarely happen)
        $defaultCurrency = Currency::where('code', 'IQD')->first();
        if (!$defaultCurrency) {
            throw new \RuntimeException('No default currency found. Please ensure IQD currency exists.');
        }

        return $defaultCurrency;
    }
}
