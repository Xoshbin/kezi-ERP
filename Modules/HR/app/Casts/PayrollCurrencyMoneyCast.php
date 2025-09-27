<?php

namespace Modules\HR\Casts;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * PayrollCurrencyMoneyCast - Uses the currency from the parent payroll.
 *
 * This cast is used for PayrollLine fields that should be stored and retrieved
 * in the payroll's currency.
 */
class PayrollCurrencyMoneyCast extends MoneyCast
{
    /**
     * Resolve the currency from the parent payroll.
     */
    protected function resolveCurrency(Model $model): Currency
    {
        // Check if the model has a payroll relationship
        if (method_exists($model, 'payroll') && $model->getAttribute('payroll_id')) {
            $payroll = $model->payroll()->with('currency')->first();
            if ($payroll && $payroll->currency) {
                return $payroll->currency;
            }
        }

        throw new InvalidArgumentException('Could not resolve payroll currency for model '.get_class($model).'. Please ensure the model has a valid payroll relationship.');
    }
}
