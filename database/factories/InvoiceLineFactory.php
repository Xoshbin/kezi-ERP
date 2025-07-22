<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceLine>
 */
class InvoiceLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory()->create()->id,
            'product_id' => Product::factory()->create()->id,
            'description' => $this->faker->sentence(),
            'quantity' => $this->faker->randomFloat(2, 1, 100),
            'unit_price' => $this->faker->randomFloat(2, 10, 1000),
            'tax_id' => Tax::factory()->create()->id,
            'subtotal' => function (array $attributes) {
                return round($attributes['quantity'] * $attributes['unit_price'], 2);
            },
            'total_line_tax' => $this->faker->randomFloat(2, 0, 200),
            'income_account_id' => Account::factory()->create()->id,
        ];
    }
}
