<?php

namespace Modules\Inventory\Database\Factories;

use App\Models\Company;
use App\Models\Lot;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\StockQuant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockQuant>
 */
class StockQuantFactory extends Factory
{
    protected $model = StockQuant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(0, 100);
        $reservedQuantity = $this->faker->numberBetween(0, min($quantity, 20));

        return [
            'company_id' => Company::factory(),
            'product_id' => \Modules\Product\Models\Product::factory(),
            'location_id' => StockLocation::factory(),
            'lot_id' => null,
            'quantity' => $quantity,
            'reserved_quantity' => $reservedQuantity,
        ];
    }

    /**
     * Indicate that the quant is for a specific lot.
     */
    public function withLot(): static
    {
        return $this->state(fn (array $attributes) => [
            'lot_id' => Lot::factory(),
        ]);
    }

    /**
     * Indicate that the quant has no stock.
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
            'reserved_quantity' => 0,
        ]);
    }

    /**
     * Indicate that the quant is fully reserved.
     */
    public function fullyReserved(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $attributes['quantity'] ?? $this->faker->numberBetween(10, 50);
            return [
                'quantity' => $quantity,
                'reserved_quantity' => $quantity,
            ];
        });
    }

    /**
     * Set specific quantities.
     */
    public function withQuantities(float $quantity, float $reserved = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
            'reserved_quantity' => $reserved,
        ]);
    }
}
