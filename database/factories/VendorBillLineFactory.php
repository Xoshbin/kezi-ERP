<?php

namespace Database\Factories;

use App\Models\VendorBillLine;
use App\Models\Tax;
use Brick\Money\Money;
use App\Models\Account;
use App\Models\Product;
use App\Models\VendorBill;
use App\Models\AnalyticAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorBillLine>
 */
class VendorBillLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_bill_id' => VendorBill::factory()->create()->id,
            'product_id' => Product::factory()->create()->id,
            'description' => $this->faker->sentence(4),
            'quantity' => $this->faker->randomFloat(2, 1, 100),
            'unit_price' => Money::of($this->faker->randomFloat(2, 10, 1000), 'USD'),
            'tax_id' => Tax::factory()->create()->id,
            'subtotal' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
            'total_line_tax' => Money::of($this->faker->randomFloat(2, 0, 200), 'USD'),
            'expense_account_id' => Account::factory()->create()->id,
            'analytic_account_id' => AnalyticAccount::factory()->create()->id, // Nullable, can be used for detailed tracking
        ];
    }
}
