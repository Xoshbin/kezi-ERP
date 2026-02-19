<?php

namespace Kezi\Pos\Casts;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Kezi\Foundation\Casts\MoneyCast;
use Kezi\Foundation\Models\Currency;
use Kezi\Pos\Models\PosOrder;

class PosOrderLineMoneyCast extends MoneyCast
{
    protected function resolveCurrency(Model $model): Currency
    {
        if (method_exists($model, 'order')) {
            // @phpstan-ignore-next-line
            $order = $model->order;

            if (! $model->relationLoaded('order')) {
                // If we have pos_order_id, fetch it
                if ($model->getAttribute('pos_order_id')) {
                    $order = $model->order()->with('currency')->first();
                }
            }

            if ($order && $order->currency) {
                return $order->currency;
            }
        }

        throw new InvalidArgumentException('Could not resolve currency for PosOrderLine. Ensure order relationship is valid.');
    }

    /**
     * Override set to handle creation time when relation might not be loaded but foreign key is present in attributes?
     * Actually default set calls resolveCurrency.
     * But during creation: PosOrderLine::create(['pos_order_id' => 1, 'amount' => 10])
     * $model->posOrder is null. $model->getAttribute('pos_order_id') might be null if strictly accessing property?
     * On 'set', attributes array is passed.
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
            // Try to resolve using attributes
            $currency = null;
            if (isset($attributes['pos_order_id'])) {
                $order = PosOrder::with('currency')->find($attributes['pos_order_id']);
                $currency = $order?->currency;
            }

            if (! $currency) {
                $currency = $this->resolveCurrency($model);
            }

            $money = Money::of($value, $currency->code, null, \Brick\Math\RoundingMode::HALF_UP);

            return [$key => $money->getMinorAmount()->toInt()];
        }

        throw new InvalidArgumentException('Invalid value for MoneyCast: must be numeric or Money instance.');
    }
}
