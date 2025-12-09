<?php

namespace Modules\Foundation\Database\Factories;

use Carbon\Carbon;
use App\Models\Company;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\CurrencyRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurrencyRate>
 */
class CurrencyRateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Modules\Foundation\Models\CurrencyRate>
     */
    protected $model = \Modules\Foundation\Models\CurrencyRate::class;

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
