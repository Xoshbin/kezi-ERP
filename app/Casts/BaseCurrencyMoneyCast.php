<?php

namespace App\Casts;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;

/**
 * BaseCurrencyMoneyCast - Always uses the company's base currency.
 *
 * This cast is used for fields that should always be stored and retrieved
 * in the company's base currency, such as debit and credit amounts in
 * journal entry lines.
 */
class BaseCurrencyMoneyCast extends MoneyCast
{
    /**
     * Resolve the currency for this cast.
     * Always returns the company's base currency.
     */
    protected function resolveCurrency(Model $model): Currency
    {
        // For JournalEntryLine, get company currency through journal entry
        if (method_exists($model, 'journalEntry')) {
            if ($model->relationLoaded('journalEntry')) {
                return $model->journalEntry->company->currency;
            }

            // Load the relationship if not already loaded
            $model->load('journalEntry.company.currency');
            return $model->journalEntry->company->currency;
        }

        // Fallback to parent implementation
        return parent::resolveCurrency($model);
    }
}
