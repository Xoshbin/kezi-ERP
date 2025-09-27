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
        $currencies = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY', 'SEK', 'NZD'];

        // Use random selection instead of static counter to avoid parallel execution conflicts
        $currencyCode = $this->faker->randomElement($currencies);

        return [
            'name' => $currencyCode . ' Currency',
            'code' => $currencyCode,
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
        ];
    }

    public function configure(): self
    {
        return $this->afterMaking(function (\Modules\Foundation\Models\Currency $currency) {
            //
        })->afterCreating(function (\Modules\Foundation\Models\Currency $currency) {
            if ($currency->code === 'IQD') {
                $currency->decimal_places = 3;
                $currency->save();
            }
        });
    }

    /**
     * Create a currency with firstOrCreate to avoid duplicates in parallel tests
     */
    public function createSafely(array $attributes = []): \Modules\Foundation\Models\Currency
    {
        $definition = $this->definition();
        $mergedAttributes = array_merge($definition, $attributes);

        return \Modules\Foundation\Models\Currency::firstOrCreate(
            ['code' => $mergedAttributes['code']],
            $mergedAttributes
        );
    }
}
