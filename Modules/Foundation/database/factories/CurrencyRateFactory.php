<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Currency;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CurrencyRate>
 */
class CurrencyRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'currency_id' => Currency::factory()->createSafely(),
            'rate' => $this->faker->randomFloat(6, 0.1, 10.0),
            'effective_date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'source' => $this->faker->randomElement(['manual', 'api', 'bank', 'central_bank']),
        ];
    }

    /**
     * Indicate that the rate is from today.
     */
    public function today(): static
    {
        return $this->state(fn(array $attributes) => [
            'effective_date' => Carbon::today(),
        ]);
    }

    /**
     * Indicate that the rate is from a specific date.
     */
    public function forDate(Carbon $date): static
    {
        return $this->state(fn(array $attributes) => [
            'effective_date' => $date,
        ]);
    }

    /**
     * Indicate that the rate is from API source.
     */
    public function fromApi(): static
    {
        return $this->state(fn(array $attributes) => [
            'source' => 'api',
        ]);
    }

    /**
     * Indicate that the rate is manual entry.
     */
    public function manual(): static
    {
        return $this->state(fn(array $attributes) => [
            'source' => 'manual',
        ]);
    }
}
