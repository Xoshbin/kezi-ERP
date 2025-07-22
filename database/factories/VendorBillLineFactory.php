<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\AnalyticAccount;
use App\Models\Product;
use App\Models\Tax;
use App\Models\VendorBill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VendorBillLine>
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
            'description' => $this->faker->sentence(),
            'quantity' => $this->faker->randomFloat(2, 1, 100),
            'unit_price' => $this->faker->randomFloat(2, 10, 1000),
            'tax_id' => Tax::factory()->create()->id,
            'subtotal' => function (array $attributes) {
                return round(($attributes['quantity'] ?? 1) * ($attributes['unit_price'] ?? 0), 2);
            },
            'total_line_tax' => $this->faker->randomFloat(2, 0, 200),
            'expense_account_id' => Account::factory()->create()->id,
            'analytic_account_id' => AnalyticAccount::factory()->create()->id, // Nullable, can be used for detailed tracking
        ];
    }
}
