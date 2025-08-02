<?php

namespace App\Casts;

use Brick\Money\Money;
use App\Models\Currency;
use Brick\Math\RoundingMode;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class MoneyCast implements CastsAttributes
{
    /**
     * A cache for Currency models to avoid repeated database queries.
     *
     * @var array<int, Currency>
     */
    protected static array $currencyCache = [];

    /**
     * Clear the currency cache. Used for test isolation.
     */
    public static function clearCache(): void
    {
        self::$currencyCache = [];
    }

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
        Log::info('MoneyCast received value:', [
            'class' => is_object($value) ? get_class($value) : gettype($value),
            'value_string' => (string) $value,
        ]);
        if (is_null($value)) {
            return null;
        }

        // If the value is numeric (int, float, or numeric string) but not already a Money object,
        // create a Money object from it, treating it as a major unit value.
        if (is_numeric($value) && !$value instanceof Money) {
            $currency = $this->resolveCurrency($model, $attributes);
            $value = Money::of($value, $currency->code, null, RoundingMode::HALF_UP);
        } elseif (!$value instanceof Money) {
            throw new InvalidArgumentException('The given value is not a Money instance or numeric.');
        }

        return $value->getMinorAmount()->toInt();
    }

    /**
     * Resolves the currency from the model's attributes or relationships.
     */
    protected function resolveCurrency(Model $model, array $attributes): Currency
    {
        $currencyId = $attributes['currency_id'] ?? null;

        // If currency_id is not in attributes but the model has it as a property, use it
        if (!$currencyId && isset($model->currency_id)) {
            $currencyId = $model->currency_id;
        }

        if (!$currencyId) {
            $currencyId = $this->findCurrencyIdInRelations($model, $attributes);
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
    protected function findCurrencyIdInRelations(Model $model, array $attributes): ?int
    {
        // A list of common parent relationships that hold a currency_id.
        $possibleRelations = ['invoice', 'vendorBill', 'journalEntry', 'payment', 'adjustmentDocument', 'bankStatement', 'budget', 'asset'];

        foreach ($possibleRelations as $relationName) {
            if (! method_exists($model, $relationName)) {
                continue;
            }

            // Strategy 1: If relation is already loaded, use it.
            if ($model->relationLoaded($relationName) && $model->{$relationName}) {
                return $model->{$relationName}->currency_id;
            }

            // Strategy 2: Use the foreign key from the attributes array if available.
            // This is crucial for the `set` method, where the relation might not be loaded yet.
            $foreignKey = $model->{$relationName}()->getForeignKeyName();
            if (isset($attributes[$foreignKey])) {
                // Find the parent model without relying on the current model's state
                $parent = $model->{$relationName}()->getRelated()->newQuery()->find($attributes[$foreignKey]);
                if ($parent) {
                    return $parent->currency_id;
                }
            }

            // Strategy 3: Fallback to loading the relation from the database if the model exists.
            if ($model->exists) {
                $model->loadMissing($relationName);
                if ($model->relationLoaded($relationName) && $model->{$relationName}) {
                    return $model->{$relationName}->currency_id;
                }
            }
        }

        return null;
    }
}
