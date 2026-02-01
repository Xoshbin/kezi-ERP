<?php

namespace Jmeryar\Payment\Database\Factories\PettyCash;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\Payment\Models\PettyCash\PettyCashVoucher;

class PettyCashVoucherFactory extends Factory
{
    protected $model = PettyCashVoucher::class;

    public function definition(): array
    {
        return [
            'voucher_number' => 'PCV-'.$this->faker->unique()->numberBetween(1000, 9999),
            'amount' => Money::of($this->faker->numberBetween(5000, 100000), 'IQD'),
            'voucher_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'description' => $this->faker->sentence(),
            'status' => 'draft',
            'receipt_reference' => $this->faker->optional()->bothify('REC-####'),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function posted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'posted',
        ]);
    }
}
