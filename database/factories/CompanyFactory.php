<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'address' => $this->faker->address,
            'tax_id' => $this->faker->unique()->numerify('##########'),
            'currency_id' => Currency::firstOrCreate(['code' => 'IQD'], ['name' => 'Iraqi Dinar', 'symbol' => 'IQD', 'exchange_rate' => 1.0])->id,
            'fiscal_country' => $this->faker->countryCode,
            'parent_company_id' => null,
        ];
    }
}
