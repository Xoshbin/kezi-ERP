<?php

namespace Modules\Inventory\Database\Factories;

use App\Models\InventoryCostLayer;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryCostLayer>
 */
class InventoryCostLayerFactory extends Factory
{
    protected $model = InventoryCostLayer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 100);
        $costPerUnit = Money::of($this->faker->numberBetween(10000, 100000), 'IQD');

        return [
            'product_id' => \Modules\Product\Models\Product::factory(),
            'quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'cost_per_unit' => $costPerUnit,
            'purchase_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'source_type' => 'Test',
            'source_id' => 1,
        ];
    }

    /**
     * Create a cost layer with specific remaining quantity
     */
    public function withRemainingQuantity(float $remainingQuantity): static
    {
        return $this->state(fn (array $attributes) => [
            'remaining_quantity' => $remainingQuantity,
        ]);
    }

    /**
     * Create a cost layer with specific cost per unit
     */
    public function withCostPerUnit(Money $costPerUnit): static
    {
        return $this->state(fn (array $attributes) => [
            'cost_per_unit' => $costPerUnit,
        ]);
    }

    /**
     * Create a cost layer for a specific product
     */
    public function forProduct(\Modules\Product\Models\Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
        ]);
    }
}
