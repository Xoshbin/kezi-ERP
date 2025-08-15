<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
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
            'name' => $this->faker->unique()->country . ' Dollar',
            'code' => $this->faker->unique()->currencyCode,
            'symbol' => $this->faker->unique()->lexify('?'),
            'exchange_rate' => $this->faker->randomFloat(4, 0.5, 1.5),
            'decimal_places' => 2,
        ];
    }

    public function configure(): self
    {
        return $this->afterMaking(function (Currency $currency) {
            //
        })->afterCreating(function (Currency $currency) {
            if ($currency->code === 'IQD') {
                $currency->decimal_places = 3;
                $currency->save();
            }
        });
    }
}
