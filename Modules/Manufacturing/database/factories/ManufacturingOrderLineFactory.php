<?php

namespace Modules\Manufacturing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Manufacturing\Models\ManufacturingOrder;
use Modules\Manufacturing\Models\ManufacturingOrderLine;
use Modules\Product\Models\Product;

class ManufacturingOrderLineFactory extends Factory
{
    protected $model = ManufacturingOrderLine::class;

    public function definition(): array
    {
        return [
            'company_id' => fn (array $attributes) => ManufacturingOrder::find($attributes['manufacturing_order_id'])->company_id,
            'manufacturing_order_id' => ManufacturingOrder::factory(),
            'product_id' => fn (array $attributes) => Product::factory()->create([
                'company_id' => ManufacturingOrder::find($attributes['manufacturing_order_id'])->company_id,
            ])->id,
            'quantity_required' => $this->faker->randomFloat(4, 1, 100),
            'quantity_consumed' => 0,
            'unit_cost' => $this->faker->numberBetween(1000, 100000),
            'currency_code' => 'IQD',
            'stock_move_id' => null,
        ];
    }

    public function forOrder(ManufacturingOrder $mo): self
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $mo->company_id,
            'manufacturing_order_id' => $mo->id,
        ]);
    }
}
