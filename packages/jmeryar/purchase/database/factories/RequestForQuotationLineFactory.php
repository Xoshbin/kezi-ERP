<?php

namespace Jmeryar\Purchase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Jmeryar\Purchase\Models\RequestForQuotationLine>
 */
class RequestForQuotationLineFactory extends Factory
{
    protected $model = \Jmeryar\Purchase\Models\RequestForQuotationLine::class;

    public function definition(): array
    {
        return [
            'rfq_id' => \Jmeryar\Purchase\Models\RequestForQuotation::factory(),
            'product_id' => \Jmeryar\Product\Models\Product::factory(),
            'tax_id' => null,
            'description' => $this->faker->sentence(3),
            'quantity' => $this->faker->randomFloat(2, 1, 100),
            'unit' => 'pcs',
            'unit_price' => \Brick\Money\Money::of($this->faker->numberBetween(100, 10000), 'USD'),
            'subtotal' => \Brick\Money\Money::of(0, 'USD'),
            'tax_amount' => \Brick\Money\Money::of(0, 'USD'),
            'total' => \Brick\Money\Money::of(0, 'USD'),
        ];
    }
}
