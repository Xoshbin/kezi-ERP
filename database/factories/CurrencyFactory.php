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
        static $currencies = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY', 'SEK', 'NZD'];
        static $counter = 0;

        $currencyCode = $currencies[$counter % count($currencies)];
        $counter++;

        return [
            'name' => $currencyCode.' Currency',
            'code' => $currencyCode,
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
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
