<?php

namespace Jmeryar\HR\Casts;

use App\Models\Company;
use Exception;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Jmeryar\Foundation\Casts\MoneyCast;
use Jmeryar\Foundation\Models\Currency;

/**
 * SalaryCurrencyMoneyCast - Uses the currency_id field.
 *
 * This cast is used for salary fields in Position model that should be stored
 * and retrieved in the currency specified by the currency_id field.
 */
class SalaryCurrencyMoneyCast extends MoneyCast
{
    /**
     * Resolve the currency from the 'currency_id' field.
     */
    protected function resolveCurrency(Model $model): Currency
    {
        // Check for currency_id field
        if (isset($model->currency_id)) {
            $currency = Currency::findOrFail($model->currency_id);
            // Ensure we have a single Currency model, not a collection
            if ($currency instanceof Collection) {
                $currency = $currency->first();
                if (! $currency) {
                    throw new InvalidArgumentException('Salary currency collection is empty');
                }
            }

            return $currency;
        }

        // If no currency_id is set, fall back to company's base currency
        if ($model->relationLoaded('company') && $model->getRelationValue('company')) {
            /** @var Company $company */
            $company = $model->getRelationValue('company');

            return $company->currency;
        }

        // Fallback: If relationships are not loaded, perform database queries
        if (method_exists($model, 'company') && isset($model->company_id)) {
            $company = $model->company()->with('currency')->first();
            if ($company && $company->currency) {
                return $company->currency;
            }
        }

        // Last resort: Try to get currency from Filament tenant context
        try {
            $tenant = Filament::getTenant();
            if ($tenant instanceof Company) {
                return $tenant->currency;
            }
        } catch (Exception) {
            // Ignore tenant resolution errors
        }

        throw new InvalidArgumentException('Could not resolve salary currency for model '.get_class($model).'. Please ensure the model has a valid currency_id or company relationship.');
    }
}
