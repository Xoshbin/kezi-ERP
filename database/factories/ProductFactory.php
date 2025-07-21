<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
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
            'name' => $this->faker->word(),
            'sku' => strtoupper($this->faker->bothify('SKU-####')),
            'description' => $this->faker->sentence(),
            'unit_price' => $this->faker->randomFloat(2, 10, 1000),
            'type' => $this->faker->randomElement(['product', 'service']),
            'income_account_id' => Account::factory()->create()->id,
            'expense_account_id' => Account::factory()->create()->id,
            'is_active' => $this->faker->boolean(),
        ];
    }
}
