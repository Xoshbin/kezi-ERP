<?php

namespace Modules\Accounting\Database\Factories;

use Brick\Money\Money;
use App\Models\Company;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Enums\Assets\AssetStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
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
            'currency_id' => function (array $attributes) {
                return Company::find($attributes['company_id'])->currency_id;
            },
            'name' => $this->faker->word,
            'purchase_date' => $this->faker->date(),
            'purchase_value' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
            'salvage_value' => Money::of(0, 'USD'),
            'useful_life_years' => $this->faker->numberBetween(3, 10),
            'depreciation_method' => 'straight_line',
            // Create real accounts instead of using random numbers
            'asset_account_id' => Account::factory(),
            'depreciation_expense_account_id' => Account::factory(),
            'accumulated_depreciation_account_id' => Account::factory(),
            'status' => AssetStatus::Draft,
        ];
    }
}
