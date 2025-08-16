<?php

namespace App\Casts;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;

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
     * Resolve the currency for this cast.
     * Returns the original transaction currency from original_currency_id.
     */
    protected function resolveCurrency(Model $model): Currency
    {
        // Get the original currency ID from the model
        $originalCurrencyId = $model->original_currency_id ?? null;

        if (!$originalCurrencyId) {
            // Fallback to parent implementation if no original_currency_id
            return parent::resolveCurrency($model);
        }

        // Return the currency by ID
        return Currency::findOrFail($originalCurrencyId);
    }
}
