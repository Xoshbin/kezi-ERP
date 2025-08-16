<?php

namespace App\Casts;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;

/**
 * CompanyCurrencyMoneyCast - Always uses the company's base currency.
 * 
 * This cast is used for fields in the JournalEntry model that should 
 * always be stored and retrieved in the company's base currency,
 * such as total_debit and total_credit.
 */
class CompanyCurrencyMoneyCast extends MoneyCast
{
    /**
     * Resolve the currency for this cast.
     * Always returns the company's base currency.
     */
    protected function resolveCurrency(Model $model): Currency
    {
        // For JournalEntry, get company currency directly
        if ($model->relationLoaded('company')) {
            return $model->company->currency;
        }
        
        // Load the relationship if not already loaded
        $model->load('company.currency');
        return $model->company->currency;
    }
}
