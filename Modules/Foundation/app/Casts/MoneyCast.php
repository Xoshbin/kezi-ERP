<?php

namespace Modules\Foundation\Casts;

use App\Models\Currency;
use Brick\Math\Exception\MathException;
use Brick\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Abstract base class for all Money casting operations.
 *
 * This class provides the core logic for converting between database values
 * and Money objects, but cannot be used directly. You MUST use one of its
 * specific children (BaseCurrencyMoneyCast, DocumentCurrencyMoneyCast, etc.)
 * which implement the currency resolution strategy.
 *
 * This design follows the Strategy pattern, ensuring each cast has a single,
 * explicit responsibility for currency context resolution.
 */
/**
 * @implements CastsAttributes<\Brick\Money\Money|null, \Brick\Money\Money|int|float|string|null>
 */
abstract class MoneyCast implements CastsAttributes
{
    /**
     * Get the real value of an attribute.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws MathException
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
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
     * @param  array<string, mixed>  $attributes
     * @return array<string, int|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
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

        throw new InvalidArgumentException('Invalid value for MoneyCast: must be numeric or Money instance.');
    }

    /**
     * Resolve the currency from the model's context.
     * This method MUST be implemented by any child class.
     *
     * Each implementation should have a single, explicit strategy
     * for determining the currency context (e.g., company base currency,
     * document currency, etc.) to ensure predictable behavior.
     */
    abstract protected function resolveCurrency(Model $model): \Modules\Foundation\Models\Currency;
}
