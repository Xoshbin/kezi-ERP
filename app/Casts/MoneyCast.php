<?php

namespace App\Casts;

use Brick\Money\Money;
use App\Models\Currency;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class MoneyCast implements CastsAttributes
{
    /**
     * A cache for Currency models to avoid repeated database queries.
     *
     * @var array<int, Currency>
     */
    protected static array $currencyCache = [];

    /**
     * Cast the stored integer value to a Money object.
     */
    public function get($model, string $key, $value, array $attributes): ?Money
    {
        if (is_null($value)) {
            return null;
        }

        $currency = $this->resolveCurrency($model, $attributes);

        return Money::ofMinor($value, $currency->code);
    }

    /**
     * Prepare the given Money object for storage.
     */
    public function set($model, string $key, $value, array $attributes): ?int
    {
        if (is_null($value)) {
            return null;
        }

        if (!$value instanceof Money) {
            // Allow setting from numeric values for convenience in factories/seeders
            if (is_numeric($value)) {
                $currency = $this->resolveCurrency($model, $attributes);
                $value = Money::of($value, $currency->code);
            } else {
                throw new InvalidArgumentException('The given value is not a Money instance or numeric.');
            }
        }

        return $value->getMinorAmount()->toInt();
    }

    /**
     * Resolves the currency from the model's attributes or relationships.
     */
    protected function resolveCurrency(Model $model, array $attributes): Currency
    {
        $currencyId = $attributes['currency_id'] ?? null;

        if (!$currencyId) {
            $currencyId = $this->findCurrencyIdInRelations($model);
        }

        if (!$currencyId) {
            // As a last resort, check for a company relationship
            if (method_exists($model, 'company')) {
                 $model->loadMissing('company.currency');
                 if ($model->company) {
                     $currencyId = $model->company->currency_id;
                 }
            }
        }

        if (!$currencyId) {
            throw new InvalidArgumentException('Could not resolve currency_id for model ' . get_class($model));
        }

        if (!isset(self::$currencyCache[$currencyId])) {
            self::$currencyCache[$currencyId] = Currency::findOrFail($currencyId);
        }

        return self::$currencyCache[$currencyId];
    }

    /**
     * Intelligently find currency_id from common relationships.
     */
    protected function findCurrencyIdInRelations(Model $model): ?int
    {
        // A list of common parent relationships that hold a currency_id.
        $possibleRelations = ['invoice', 'vendorBill', 'journalEntry', 'payment', 'adjustmentDocument', 'bankStatement'];

        foreach ($possibleRelations as $relationName) {
            // THIS IS THE FIX: Check if the relationship method exists before trying to load it.
            if (method_exists($model, $relationName)) {
                $model->loadMissing($relationName);
                if ($model->relationLoaded($relationName) && $model->{$relationName}) {
                    return $model->{$relationName}->currency_id;
                }
            }
        }

        return null;
    }
}
