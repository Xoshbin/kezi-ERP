<?php

namespace Jmeryar\Product\Database\Factories;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Inventory\Enums\Inventory\ValuationMethod;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = \Jmeryar\Product\Models\Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->word(),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####')),
            'description' => $this->faker->sentence(),
            'unit_price' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
            'type' => \Jmeryar\Product\Enums\Products\ProductType::Service, // Default to Service to avoid inventory complications in tests
            'inventory_valuation_method' => ValuationMethod::AVCO,
            'income_account_id' => function (array $attributes) {
                return Account::factory()->state(['company_id' => $attributes['company_id']])->create()->id;
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
                return Account::factory()->state(['company_id' => $attributes['company_id']])->create()->id;
            },
            'average_cost' => Money::of($this->faker->randomFloat(2, 50, 500), 'USD'), // Default positive average cost
            'tracking_type' => \Jmeryar\Inventory\Enums\Inventory\TrackingType::None,
            'is_active' => $this->faker->boolean(),
        ];
    }
}
