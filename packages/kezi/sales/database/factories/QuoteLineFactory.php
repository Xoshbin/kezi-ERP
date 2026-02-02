<?php

namespace Kezi\Sales\Database\Factories;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Sales\Models\Quote;
use Kezi\Sales\Models\QuoteLine;

/**
 * @extends Factory<QuoteLine>
 */
class QuoteLineFactory extends Factory
{
    protected $model = QuoteLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 10);
        $unitPrice = $this->faker->randomFloat(2, 10, 1000);
        $subtotal = $quantity * $unitPrice;

        return [
            'quote_id' => Quote::factory(),
            'product_id' => \Kezi\Product\Models\Product::factory(),
            'tax_id' => null,
            'income_account_id' => null,
            'description' => $this->faker->sentence(),
            'quantity' => $quantity,
            'unit' => $this->faker->randomElement(['piece', 'kg', 'hour', 'unit']),
            'line_order' => 0,
            'unit_price' => $unitPrice,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'subtotal' => round($subtotal, 2),
            'tax_amount' => 0,
            'total' => round($subtotal, 2),
        ];
    }

    /**
     * Set a specific unit price.
     */
    public function withUnitPrice(Money $unitPrice): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_price' => $unitPrice,
        ]);
    }

    /**
     * Apply a discount percentage.
     */
    public function withDiscount(float $percentage): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_percentage' => $percentage,
        ]);
    }

    /**
     * Set a specific quantity.
     */
    public function withQuantity(float $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }
}
