<?php

namespace App\Casts;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

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
     * Resolve the currency by finding the company's base currency.
     * This method now expects relationships to be eager-loaded to prevent N+1 issues.
     */
    protected function resolveCurrency(Model $model): Currency
    {
        // This is the most efficient path, traversing loaded relationships.
        if ($model->relationLoaded('company') && $model->company) {
            return $model->company->currency;
        }
        if ($model->relationLoaded('journalEntry') && $model->journalEntry) {
            return $model->journalEntry->company->currency;
        }
        if ($model->relationLoaded('asset') && $model->asset && $model->asset->relationLoaded('company')) {
            return $model->asset->company->currency;
        }
        // Add other common parent relationships here as needed (e.g., vendorBill)



        // Fallback: If relationships are not loaded, perform database queries
        // This is less efficient but ensures the cast always works
        if (method_exists($model, 'company') && $model->company_id) {
            $company = $model->company()->with('currency')->first();
            if ($company && $company->currency) {
                return $company->currency;
            }
        }
        if (method_exists($model, 'journalEntry') && $model->journal_entry_id) {
            $journalEntry = $model->journalEntry()->with('company.currency')->first();
            if ($journalEntry && $journalEntry->company && $journalEntry->company->currency) {
                return $journalEntry->company->currency;
            }
        }
        if (method_exists($model, 'asset') && $model->asset_id) {
            $asset = $model->asset()->with('company.currency')->first();
            if ($asset && $asset->company && $asset->company->currency) {
                return $asset->company->currency;
            }
        }

        // Last resort: Try to get currency from Filament tenant context
        try {
            $tenant = \Filament\Facades\Filament::getTenant();
            if ($tenant && method_exists($tenant, 'currency') && $tenant->currency) {
                return $tenant->currency;
            }
        } catch (\Exception $e) {
            // Ignore tenant resolution errors
        }

        // If we still can't resolve the currency, throw an exception
        throw new InvalidArgumentException('Could not resolve base currency for model ' . get_class($model) . '. Please ensure the model has a valid company relationship.');
    }
}
