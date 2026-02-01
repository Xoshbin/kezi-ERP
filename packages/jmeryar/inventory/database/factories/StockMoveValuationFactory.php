<?php

namespace Jmeryar\Inventory\Database\Factories;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\Inventory\Enums\Inventory\ValuationMethod;
use Jmeryar\Inventory\Models\StockMove;
use Jmeryar\Inventory\Models\StockMoveValuation;
use Jmeryar\Product\Models\Product;

class StockMoveValuationFactory extends Factory
{
    protected $model = StockMoveValuation::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'product_id' => Product::factory(),
            'stock_move_id' => StockMove::factory(),
            'quantity' => $this->faker->randomFloat(2, 1, 100),
            'cost_impact' => Money::of(100, 'IQD'),
            'valuation_method' => ValuationMethod::AVCO,
            'move_type' => 'incoming',
            'source_type' => StockMove::class,
            'source_id' => 1,
        ];
    }
}
