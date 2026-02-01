<?php

namespace Kezi\Inventory\Database\Factories;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Inventory\Models\InventoryCostLayer;
use Kezi\Product\Models\Product;

/**
 * @extends Factory<InventoryCostLayer>
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
            'company_id' => \App\Models\Company::factory(),
            'product_id' => function (array $attributes) {
                /** @var Product $product */
                $product = Product::factory()->state(['company_id' => $attributes['company_id']])->create();

                return $product->id;
            },
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
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
        ]);
    }
}
