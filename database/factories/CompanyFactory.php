<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'address' => $this->faker->address,
            'tax_id' => $this->faker->unique()->numerify('##########'),
            // Let Laravel handle creation unless specified otherwise in the test.
            'currency_id' => Currency::firstOrCreate(
                ['code' => 'IQD'],
                [
                    'name' => 'Iraqi Dinar',
                    'symbol' => 'IQD',
                    'is_active' => true,
                    'decimal_places' => 3,
                ]
            )->id,
            'fiscal_country' => 'IQ', // Default to Iraq as per project spec
            'parent_company_id' => null,
            'enable_reconciliation' => false, // Default to disabled for security
        ];
    }

    /**
     * Indicate that the company has reconciliation enabled.
     */
    public function withReconciliationEnabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enable_reconciliation' => true,
        ]);
    }
}
