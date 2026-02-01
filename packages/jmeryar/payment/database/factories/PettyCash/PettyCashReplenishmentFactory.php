<?php

namespace Jmeryar\Payment\Database\Factories\PettyCash;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\Payment\Models\PettyCash\PettyCashReplenishment;

class PettyCashReplenishmentFactory extends Factory
{
    protected $model = PettyCashReplenishment::class;

    public function definition(): array
    {
        return [
            'replenishment_number' => 'PCR-'.$this->faker->unique()->numberBetween(1000, 9999),
            'amount' => Money::of($this->faker->numberBetween(100000, 500000), 'IQD'),
            'replenishment_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'payment_method' => $this->faker->randomElement(['cash', 'bank_transfer', 'cheque']),
            'reference' => $this->faker->optional()->bothify('TRX-#####'),
        ];
    }
}
