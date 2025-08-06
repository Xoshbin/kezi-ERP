<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word,
            'code' => $this->faker->unique()->currencyCode,
            'symbol' => $this->faker->unique()->randomLetter(),
            'exchange_rate' => 1,
            'decimal_places' => 2,
        ];
    }
}
