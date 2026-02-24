<?php

namespace Kezi\Pos\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Pos\Models\PosReturnLine;
use Kezi\Product\Models\Product;

class PosReturnLineFactory extends Factory
{
    protected $model = PosReturnLine::class;

    public function definition(): array
    {
        return [
            'pos_return_id' => PosReturnFactory::new(),
            'original_order_line_id' => PosOrderLineFactory::new(),
            'product_id' => Product::factory(),
            'quantity_returned' => 1.0,
            'quantity_available' => 1.0,
            'unit_price' => 1000,
            'refund_amount' => 1000,
            'restocking_fee_line' => 0,
            'restock' => true,
            'item_condition' => 'good',
        ];
    }
}
