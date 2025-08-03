<?php

namespace Database\Factories;

use Brick\Money\Money;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            // A line should NOT create its own parent invoice. The test should provide it.
            'invoice_id' => Invoice::factory(),
            'product_id' => null, // Default to a descriptive line without a product
            'description' => $this->faker->sentence(),
            'quantity' => $this->faker->numberBetween(1, 5),
            'unit_price' => Money::of($this->faker->randomFloat(2, 25, 500), 'USD'),
            'tax_id' => null, // Default to no tax
            // The income account should come from the product or be specified in the test.
            'income_account_id' => Account::factory()->state(['type' => 'Income']),
            // Subtotal and tax are calculated by observers.
            'subtotal' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
            'total_line_tax' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
        ];
    }
}
