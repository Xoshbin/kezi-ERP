<?php

namespace Jmeryar\Foundation\Casts;

use Exception;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Jmeryar\Foundation\Models\Currency;

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
        if (method_exists($model, 'company') && $model->relationLoaded('company')) {
            $company = $model->getRelation('company');
            if ($company instanceof Model && method_exists($company, 'currency')) {
                $currency = $company->relationLoaded('currency') ? $company->getRelation('currency') : $company->currency()->first();
                if ($currency instanceof Currency) {
                    return $currency;
                }
            }
        }
        if (method_exists($model, 'journalEntry') && $model->relationLoaded('journalEntry')) {
            $journalEntry = $model->getRelation('journalEntry');
            if ($journalEntry instanceof Model && method_exists($journalEntry, 'company')) {
                $company = $journalEntry->relationLoaded('company') ? $journalEntry->getRelation('company') : $journalEntry->company()->first();
                if ($company instanceof Model && method_exists($company, 'currency')) {
                    $currency = $company->relationLoaded('currency') ? $company->getRelation('currency') : $company->currency()->first();
                    if ($currency instanceof Currency) {
                        return $currency;
                    }
                }
            }
        }
        if (method_exists($model, 'asset') && $model->relationLoaded('asset')) {
            $asset = $model->getRelation('asset');
            if ($asset instanceof Model && method_exists($asset, 'company')) {
                $company = $asset->relationLoaded('company') ? $asset->getRelation('company') : $asset->company()->first();
                if ($company instanceof Model && method_exists($company, 'currency')) {
                    $currency = $company->relationLoaded('currency') ? $company->getRelation('currency') : $company->currency()->first();
                    if ($currency instanceof Currency) {
                        return $currency;
                    }
                }
            }
        }
        if (method_exists($model, 'product') && $model->relationLoaded('product')) {
            $product = $model->getRelation('product');
            if ($product instanceof Model && method_exists($product, 'company')) {
                $company = $product->relationLoaded('company') ? $product->getRelation('company') : $product->company()->first();
                if ($company instanceof Model && method_exists($company, 'currency')) {
                    $currency = $company->relationLoaded('currency') ? $company->getRelation('currency') : $company->currency()->first();
                    if ($currency instanceof Currency) {
                        return $currency;
                    }
                }
            }
        }

        if (method_exists($model, 'purchaseOrder') && $model->relationLoaded('purchaseOrder')) {
            $purchaseOrder = $model->getRelation('purchaseOrder');
            if ($purchaseOrder instanceof Model && method_exists($purchaseOrder, 'company')) {
                $company = $purchaseOrder->relationLoaded('company') ? $purchaseOrder->getRelation('company') : $purchaseOrder->company()->first();
                if ($company instanceof Model && method_exists($company, 'currency')) {
                    $currency = $company->relationLoaded('currency') ? $company->getRelation('currency') : $company->currency()->first();
                    if ($currency instanceof Currency) {
                        return $currency;
                    }
                }
            }
        }

        if (method_exists($model, 'currencyRevaluation') && $model->relationLoaded('currencyRevaluation')) {
            $revaluation = $model->getRelation('currencyRevaluation');
            if ($revaluation instanceof Model && method_exists($revaluation, 'company')) {
                $company = $revaluation->relationLoaded('company') ? $revaluation->getRelation('company') : $revaluation->company()->first();
                if ($company instanceof Model && method_exists($company, 'currency')) {
                    $currency = $company->relationLoaded('currency') ? $company->getRelation('currency') : $company->currency()->first();
                    if ($currency instanceof Currency) {
                        return $currency;
                    }
                }
            }
        }

        // Add other common parent relationships here as needed (e.g., vendorBill)

        // Fallback: If relationships are not loaded, perform database queries
        // This is less efficient but ensures the cast always works
        if (method_exists($model, 'company') && $model->getAttribute('company_id')) {
            $company = $model->company()->with('currency')->first();
            if ($company && $company->currency) {
                /** @var Currency $currency */
                $currency = $company->currency;

                return $currency;
            }
        }
        if (method_exists($model, 'journalEntry') && $model->getAttribute('journal_entry_id')) {
            $journalEntry = $model->journalEntry()->with('company.currency')->first();
            if ($journalEntry && $journalEntry->company && $journalEntry->company->currency) {
                /** @var Currency $currency */
                $currency = $journalEntry->company->currency;

                return $currency;
            }
        }
        if (method_exists($model, 'asset') && $model->getAttribute('asset_id')) {
            $asset = $model->asset()->with('company.currency')->first();
            if ($asset && $asset->company && $asset->company->currency) {
                return $asset->company->currency;
            }
        }

        if (method_exists($model, 'purchaseOrder') && $model->getAttribute('purchase_order_id')) {
            $purchaseOrder = $model->purchaseOrder()->with('company.currency')->first();
            if ($purchaseOrder && $purchaseOrder->company && $purchaseOrder->company->currency) {
                return $purchaseOrder->company->currency;
            }
        }

        if (method_exists($model, 'currencyRevaluation') && $model->getAttribute('currency_revaluation_id')) {
            $revaluation = $model->currencyRevaluation()->with('company.currency')->first();
            if ($revaluation && $revaluation->company && $revaluation->company->currency) {
                return $revaluation->company->currency;
            }
        }

        if (method_exists($model, 'budget') && $model->getAttribute('budget_id')) {
            $budget = $model->budget()->with('company.currency')->first();
            if ($budget && $budget->company && $budget->company->currency) {
                return $budget->company->currency;
            }
        }

        // Last resort: Try to get currency from Filament tenant context
        try {
            $tenant = Filament::getTenant();
            if ($tenant instanceof Model && method_exists($tenant, 'currency')) {
                /** @var Currency|null $currency */
                $currency = $tenant->relationLoaded('currency') ? $tenant->getRelation('currency') : $tenant->currency()->first();
                if ($currency) {
                    return $currency;
                }
            }
        } catch (Exception) {
            // Ignore tenant resolution errors
        }

        // If we still can't resolve the currency, throw an exception
        throw new InvalidArgumentException('Could not resolve base currency for model '.get_class($model).'. Please ensure the model has a valid company relationship.');
    }
}
