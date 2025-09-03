<?php

namespace App\Casts;

use App\Models\Currency;
use Exception;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * SalaryCurrencyMoneyCast - Uses the salary_currency_id field.
 *
 * This cast is used for salary fields in Position model that should be stored
 * and retrieved in the currency specified by the salary_currency_id field.
 */
class SalaryCurrencyMoneyCast extends MoneyCast
{
    /**
     * Resolve the currency from the 'salary_currency_id' field.
     */
    protected function resolveCurrency(Model $model): Currency
    {
        // Check for salary_currency_id field
        if (isset($model->salary_currency_id)) {
            return Currency::findOrFail($model->salary_currency_id);
        }

        // If no salary_currency_id is set, fall back to company's base currency
        if ($model->relationLoaded('company') && $model->company) {
            return $model->company->currency;
        }

        // Fallback: If relationships are not loaded, perform database queries
        if (method_exists($model, 'company') && $model->company_id) {
            $company = $model->company()->with('currency')->first();
            if ($company && $company->currency) {
                return $company->currency;
            }
        }

        // Last resort: Try to get currency from Filament tenant context
        try {
            $tenant = Filament::getTenant();
            if ($tenant && method_exists($tenant, 'currency') && $tenant->currency) {
                return $tenant->currency;
            }
        } catch (Exception $e) {
            // Ignore tenant resolution errors
        }

        throw new InvalidArgumentException('Could not resolve salary currency for model '.get_class($model).'. Please ensure the model has a valid salary_currency_id or company relationship.');
    }
}
