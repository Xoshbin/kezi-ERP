<?php

namespace Database\Factories;

use App\Enums\Inventory\ReorderingRoute;
use App\Models\Company;
use App\Models\Product;
use App\Models\ReorderingRule;
use App\Models\StockLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReorderingRule>
 */
class ReorderingRuleFactory extends Factory
{
    protected $model = ReorderingRule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'product_id' => Product::factory(),
            'location_id' => StockLocation::factory(),
            'min_qty' => $this->faker->numberBetween(5, 20),
            'max_qty' => $this->faker->numberBetween(50, 100),
            'safety_stock' => $this->faker->numberBetween(2, 10),
            'multiple' => $this->faker->randomElement([1, 5, 10, 12]),
            'route' => $this->faker->randomElement(ReorderingRoute::cases()),
            'lead_time_days' => $this->faker->numberBetween(1, 30),
            'active' => true,
        ];
    }

    /**
     * Indicate that the rule is for min/max reordering.
     */
    public function minMax(): static
    {
        return $this->state(fn (array $attributes) => [
            'route' => ReorderingRoute::MinMax,
            'min_qty' => $this->faker->numberBetween(5, 20),
            'max_qty' => $this->faker->numberBetween(50, 100),
        ]);
    }

    /**
     * Indicate that the rule is for make-to-order.
     */
    public function mto(): static
    {
        return $this->state(fn (array $attributes) => [
            'route' => ReorderingRoute::MTO,
            'min_qty' => 0,
            'max_qty' => 0,
            'safety_stock' => 0,
        ]);
    }

    /**
     * Indicate that the rule is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
