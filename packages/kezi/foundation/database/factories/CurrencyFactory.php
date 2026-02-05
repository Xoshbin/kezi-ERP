<?php

namespace Kezi\Foundation\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Foundation\Models\Currency;

/**
 * @extends Factory<\Kezi\Foundation\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = \Kezi\Foundation\Models\Currency::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currencies = ['IQD', 'USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY', 'SEK', 'NZD'];

        // Default to IQD for stability in tests, as it's the primary currency in the app
        $currencyCode = 'IQD';
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
        return $this->afterMaking(function (\Kezi\Foundation\Models\Currency $currency) {
            //
        })->afterCreating(function (\Kezi\Foundation\Models\Currency $currency) {
            if ($currency->code === 'IQD') {
                $currency->decimal_places = 3;
                $currency->save();
            }
        });
    }

    /**
     * Create a currency with firstOrCreate to avoid duplicates in parallel tests
     */
    public function createSafely(array $attributes = []): \Kezi\Foundation\Models\Currency
    {
        $definition = $this->definition();
        $mergedAttributes = array_merge($definition, $attributes);

        return \Kezi\Foundation\Models\Currency::firstOrCreate(
            ['code' => $mergedAttributes['code']],
            $mergedAttributes
        );
    }
}
