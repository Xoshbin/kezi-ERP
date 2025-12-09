<?php

namespace Modules\Purchase\Database\Factories;

use Brick\Money\Money;
use Modules\Accounting\Models\Tax;
use Modules\Product\Models\Product;
use Modules\Accounting\Models\Account;
use Modules\Purchase\Models\VendorBill;
use Modules\Accounting\Models\AnalyticAccount;
use Modules\Product\Enums\Products\ProductType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorBillLine>
 */
class VendorBillLineFactory extends Factory
{
    protected $model = \Modules\Purchase\Models\VendorBillLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_bill_id' => VendorBill::factory(),
            'company_id' => function (array $attributes) {
                return VendorBill::find($attributes['vendor_bill_id'])->company_id;
            },
            'product_id' => Product::factory()->state(['type' => ProductType::Service]),
            'description' => $this->faker->sentence(4),
            'quantity' => $this->faker->randomFloat(2, 1, 100),
            'unit_price' => Money::of($this->faker->randomFloat(2, 10, 1000), 'USD'),
            'tax_id' => Tax::factory(),
            'subtotal' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
            'total_line_tax' => Money::of($this->faker->randomFloat(2, 0, 200), 'USD'),
            'expense_account_id' => Account::factory(),
            'analytic_account_id' => AnalyticAccount::factory(), // Nullable, can be used for detailed tracking
        ];
    }
}
