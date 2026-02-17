<?php

namespace Kezi\Pos\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosOrderLine;
use Kezi\Product\Models\Product;

class PosOrderLineFactory extends Factory
{
    protected $model = PosOrderLine::class;

    public function definition(): array
    {
        return [
            'pos_order_id' => PosOrder::factory(),
            'product_id' => Product::factory(),
            'quantity' => 1,
            'unit_price' => 1000,
            'tax_amount' => 100,
            'total_amount' => 1100,
            'metadata' => [],
        ];
    }
}
