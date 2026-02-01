<?php

namespace Jmeryar\Foundation\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\Foundation\Models\Currency;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = \Jmeryar\Foundation\Models\Currency::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currencies = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY', 'SEK', 'NZD'];

        // Avoid relying on Faker providers; use native random selection for stability
        $currencyCode = $currencies[array_rand($currencies)];
        $decimalPlaces = match ($currencyCode) {
            'JPY' => 0,
            'IQD' => 3,
            default => 2,
        };

        return [
            'name' => $currencyCode.' Currency',
            'code' => $currencyCode,
            'symbol' => $currencyCode === 'USD' ? '$' : ($currencyCode === 'JPY' ? '¥' : $currencyCode),
            'decimal_places' => $decimalPlaces,
            'is_active' => true,
        ];
    }

    public function configure(): self
    {
        return $this->afterMaking(function (\Jmeryar\Foundation\Models\Currency $currency) {
            //
        })->afterCreating(function (\Jmeryar\Foundation\Models\Currency $currency) {
            if ($currency->code === 'IQD') {
                $currency->decimal_places = 3;
                $currency->save();
            }
        });
    }

    /**
     * Create a currency with firstOrCreate to avoid duplicates in parallel tests
     */
    public function createSafely(array $attributes = []): \Jmeryar\Foundation\Models\Currency
    {
        $definition = $this->definition();
        $mergedAttributes = array_merge($definition, $attributes);

        return \Jmeryar\Foundation\Models\Currency::firstOrCreate(
            ['code' => $mergedAttributes['code']],
            $mergedAttributes
        );
    }
}
