<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tax>
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
            'rate' => $this->faker->randomFloat(2, 0, 100),
            'type' => $this->faker->randomElement(['Sales', 'Purchase', 'Both']),
            'is_active' => $this->faker->boolean,
            'tax_account_id' => Account::factory()->create()->id,
        ];
    }
}
