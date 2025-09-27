<?php

namespace Modules\Foundation\Casts;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

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
    protected function resolveCurrency(Model $model): \Modules\Foundation\Models\Currency
    {
        // Check for original_currency_id (used in journal entry lines)
        if (isset($model->original_currency_id)) {
            $currency = \Modules\Foundation\Models\Currency::findOrFail($model->original_currency_id);
            // Ensure we have a single Currency model, not a collection
            if ($currency instanceof \Illuminate\Database\Eloquent\Collection) {
                $currency = $currency->first();
                if (! $currency) {
                    throw new InvalidArgumentException('Original currency collection is empty');
                }
            }

            return $currency;
        }

        // Check for foreign_currency_id (used in bank statement lines)
        if (isset($model->foreign_currency_id)) {
            $currency = \Modules\Foundation\Models\Currency::findOrFail($model->foreign_currency_id);
            // Ensure we have a single Currency model, not a collection
            if ($currency instanceof \Illuminate\Database\Eloquent\Collection) {
                $currency = $currency->first();
                if (! $currency) {
                    throw new InvalidArgumentException('Foreign currency collection is empty');
                }
            }

            return $currency;
        }

        // Return the currency by ID
        throw new InvalidArgumentException('Model does not have an original_currency_id or foreign_currency_id for OriginalCurrencyMoneyCast.');
    }
}
