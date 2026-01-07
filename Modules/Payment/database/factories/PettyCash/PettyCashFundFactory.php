<?php

namespace Modules\Payment\Database\Factories\PettyCash;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Payment\Models\PettyCash\PettyCashFund;

class PettyCashFundFactory extends Factory
{
    protected $model = PettyCashFund::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true).' Fund',
            'imprest_amount' => Money::of($this->faker->numberBetween(100000, 1000000), 'IQD'),
            'current_balance' => Money::of($this->faker->numberBetween(50000, 500000), 'IQD'),
            'status' => 'active',
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'current_balance' => Money::zero('IQD'),
        ]);
    }
}
