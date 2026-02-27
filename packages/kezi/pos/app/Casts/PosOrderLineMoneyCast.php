<?php

namespace Kezi\Pos\Casts;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Kezi\Foundation\Casts\MoneyCast;
use Kezi\Foundation\Models\Currency;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosReturn;

class PosOrderLineMoneyCast extends MoneyCast
{
    protected function resolveCurrency(Model $model): Currency
    {
        // Handle PosOrderLine
        if (method_exists($model, 'order') || isset($model->pos_order_id)) {
            $order = $model->relationLoaded('order') ? $model->order : null;

            if (! $order && $model->getAttribute('pos_order_id')) {
                $order = PosOrder::with('currency')->find($model->getAttribute('pos_order_id'));
            }

            if ($order && $order->currency) {
                return $order->currency;
            }
        }

        // Handle PosReturnLine
        if (method_exists($model, 'posReturn') || isset($model->pos_return_id)) {
            $return = $model->relationLoaded('posReturn') ? $model->posReturn : null;

            if (! $return && $model->getAttribute('pos_return_id')) {
                $return = PosReturn::with('currency')->find($model->getAttribute('pos_return_id'));
            }

            if ($return && $return->currency) {
                return $return->currency;
            }
        }

        throw new InvalidArgumentException('Could not resolve currency for '.get_class($model).'. Ensure relationship is valid.');
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
