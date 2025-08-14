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
        // Generate a more unique currency code to avoid collisions in parallel tests
        // Include process ID and timestamp to ensure uniqueness across parallel processes
        $processId = getmypid();
        $timestamp = time();
        $uniqueCode = strtoupper(substr(md5($processId . $timestamp . $this->faker->unique()->word), 0, 3));

        return [
            'name' => $this->faker->unique()->country . ' Dollar',
            'code' => $uniqueCode,
            'symbol' => $this->faker->lexify('?'),
            'exchange_rate' => $this->faker->randomFloat(4, 0.5, 1.5),
            'decimal_places' => 2,
        ];
    }

    public function configure(): self
    {
        return $this->afterCreating(function (\App\Models\Currency $currency) {
            if ($currency->code === 'IQD') {
                $currency->decimal_places = 3;
                $currency->save();
            }
        });
    }
}
