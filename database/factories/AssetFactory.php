<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->word,
            'purchase_date' => $this->faker->date(),
            'purchase_value' => 120000,
            'salvage_value' => 0,
            'useful_life_years' => $this->faker->numberBetween(3, 10),
            'depreciation_method' => 'straight_line',
            // Create real accounts instead of using random numbers
            'asset_account_id' => Account::factory(),
            'depreciation_expense_account_id' => Account::factory(),
            'accumulated_depreciation_account_id' => Account::factory(),
            'status' => 'active',
        ];
    }
}
