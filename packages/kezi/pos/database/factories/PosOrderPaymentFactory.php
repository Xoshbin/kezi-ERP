<?php

namespace Kezi\Pos\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Payment\Enums\Payments\PaymentMethod;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosOrderPayment;

class PosOrderPaymentFactory extends Factory
{
    protected $model = PosOrderPayment::class;

    public function definition(): array
    {
        return [
            'pos_order_id' => PosOrder::factory(),
            'payment_method' => PaymentMethod::Cash,
            'amount' => $this->faker->numberBetween(1000, 50000),
            'amount_tendered' => null,
            'change_given' => 0,
        ];
    }

    public function cash(int $amount, int $amountTendered): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentMethod::Cash,
            'amount' => $amount,
            'amount_tendered' => $amountTendered,
            'change_given' => max(0, $amountTendered - $amount),
        ]);
    }

    public function card(int $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentMethod::CreditCard,
            'amount' => $amount,
            'amount_tendered' => null,
            'change_given' => 0,
        ]);
    }
}
