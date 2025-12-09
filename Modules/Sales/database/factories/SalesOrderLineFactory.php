<?php

namespace Modules\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Sales\Models\SalesOrderLine;

/**
 * @extends Factory<SalesOrderLine>
 */
class SalesOrderLineFactory extends Factory
{
    protected $model = SalesOrderLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sales_order_id' => \Modules\Sales\Models\SalesOrder::factory(),
            'product_id' => \Modules\Product\Models\Product::factory(),
            'description' => $this->faker->sentence,
            'quantity' => $this->faker->numberBetween(1, 100),
            'quantity_delivered' => 0,
            'quantity_invoiced' => 0,
            'unit_price' => $this->faker->randomFloat(2, 10, 1000),
            'subtotal' => 0,
            'total_line_tax' => 0,
            'total' => 0,
        ];
    }
}
