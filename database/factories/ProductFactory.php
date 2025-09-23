<?php

namespace Database\Factories;

use App\Enums\Inventory\ValuationMethod;
use App\Enums\Products\ProductType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Product;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
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
            'company_id' => fn() => Company::factory()->create()->id,
            'name' => $this->faker->word(),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####')),
            'description' => $this->faker->sentence(),
            'unit_price' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
            'type' => ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::AVCO,
            'income_account_id' => function (array $attributes) {
                return Account::factory()->create(['company_id' => $attributes['company_id']])->id;
            },
            'expense_account_id' => function (array $attributes) {
                return Account::factory()->create(['company_id' => $attributes['company_id']])->id;
            },
            'default_inventory_account_id' => function (array $attributes) {
                // Ensure storable products have an inventory account by default
                return Account::factory()->create(['company_id' => $attributes['company_id']])->id;
            },
            'default_cogs_account_id' => function (array $attributes) {
                // Ensure storable products have a COGS account by default
                return Account::factory()->create(['company_id' => $attributes['company_id']])->id;
            },
            'default_stock_input_account_id' => function (array $attributes) {
                // Ensure storable products have a stock input account by default
                return Account::factory()->create(['company_id' => $attributes['company_id']])->id;
            },
            'average_cost' => Money::of($this->faker->randomFloat(2, 50, 500), 'USD'), // Default positive average cost
            'is_active' => $this->faker->boolean(),
        ];
    }
}
