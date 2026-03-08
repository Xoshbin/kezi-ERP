<?php

namespace Kezi\Foundation\Casts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Kezi\Foundation\Models\Currency;

/**
 * OriginalCurrencyMoneyCast - Uses the original transaction currency.
 *
 * This cast is used for fields that should be stored and retrieved
 * in the original transaction currency, such as original_currency_amount
 * in journal entry lines.
 */
class OriginalCurrencyMoneyCast extends MoneyCast
{
    /**
     * Resolve the currency from the 'original_currency_id' or 'foreign_currency_id' field on the line itself.
     * This cast is now strict and will not fall back to ambiguous logic.
     */
    protected function resolveCurrency(Model $model): Currency
    {
        // Check for original_currency_id (used in journal entry lines)
        if (isset($model->original_currency_id)) {
            $currency = Currency::findOrFail($model->original_currency_id);
            // Ensure we have a single Currency model, not a collection
            if ($currency instanceof Collection) {
                $currency = $currency->first();
                if (! $currency) {
                    throw new InvalidArgumentException(__('foundation::exceptions.cast.empty_original_currency'));
                }
            }

            return $currency;
        }

        // Check for foreign_currency_id (used in bank statement lines)
        if (isset($model->foreign_currency_id)) {
            $currency = Currency::findOrFail($model->foreign_currency_id);
            // Ensure we have a single Currency model, not a collection
            if ($currency instanceof Collection) {
                $currency = $currency->first();
                if (! $currency) {
                    throw new InvalidArgumentException(__('foundation::exceptions.cast.empty_foreign_currency'));
                }
            }

            return $currency;
        }

        // Check for currency_id (used in revaluation lines)
        if (isset($model->currency_id)) {
            $currency = Currency::findOrFail($model->currency_id);
            if ($currency instanceof Collection) {
                $currency = $currency->first();
                if (! $currency) {
                    throw new InvalidArgumentException(__('foundation::exceptions.cast.empty_currency'));
                }
            }

            return $currency;
        }

        // Return the currency by ID
        throw new InvalidArgumentException(__('foundation::exceptions.cast.missing_internal_currency'));
    }
}
