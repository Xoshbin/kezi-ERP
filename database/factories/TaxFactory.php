<?php

namespace Database\Factories;

use App\Enums\Accounting\TaxType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tax>
 */
class TaxFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory()->create()->id,
            'name' => $this->faker->word,
            'rate' => $this->faker->randomFloat(4, 0, 0.5), // 0% to 50%
            'type' => $this->faker->randomElement([TaxType::Sales, TaxType::Purchase, TaxType::Both]),
            'is_active' => $this->faker->boolean,
            'tax_account_id' => Account::factory()->create()->id,
        ];
    }
}
