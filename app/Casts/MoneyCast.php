<?php

namespace App\Casts;

use App\Models\Currency;
use Brick\Math\Exception\MathException;
use Brick\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class MoneyCast implements CastsAttributes
{
    /**
     * Get the real value of an attribute.
     *
     * @param array<string, mixed> $attributes
     * @throws MathException
     */
    public function get($model, string $key, $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        $currency = $this->resolveCurrency($model);

        return Money::ofMinor($value, $currency->code);
    }

    /**
     * Prepare the value for storage.
     *
     * @param array<string, mixed> $attributes
     */
    public function set($model, string $key, $value, array $attributes): ?array
    {
        if ($value === null) {
            return [$key => null];
        }

        if ($value instanceof Money) {
            return [$key => $value->getMinorAmount()->toInt()];
        }

        if (is_numeric($value)) {
            $currency = $this->resolveCurrency($model);
            $money = Money::of($value, $currency->code);
            return [$key => $money->getMinorAmount()->toInt()];
        }

        throw new InvalidArgumentException("Invalid value for MoneyCast: must be numeric or Money instance.");
    }

    /**
     * Resolve the currency from the model's context.
     */
    protected function resolveCurrency(Model $model): Currency
    {
        // This relies on the model having a `currency_id` attribute or accessor.
        if (isset($model->currency_id)) {
            return Currency::findOrFail($model->currency_id);
        }

        if (method_exists($model, 'currency') && $model->currency) {
            return $model->currency;
        }

        throw new InvalidArgumentException('Could not resolve currency for the model.');
    }
}
