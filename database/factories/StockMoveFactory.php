<?php

namespace Database\Factories;

use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Models\Company;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\StockMove;
use App\Models\StockPicking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMove>
 */
class StockMoveFactory extends Factory
{
    protected $model = StockMove::class;

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
            'quantity' => $this->faker->numberBetween(1, 100),
            'from_location_id' => StockLocation::factory(),
            'to_location_id' => StockLocation::factory(),
            'move_type' => $this->faker->randomElement(StockMoveType::cases()),
            'status' => $this->faker->randomElement(StockMoveStatus::cases()),
            'move_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'reference' => 'SM-' . $this->faker->unique()->numerify('####'),
            'source_type' => 'Test',
            'source_id' => 1,
            'picking_id' => null,
            'created_by_user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the move is a receipt.
     */
    public function receipt(): static
    {
        return $this->state(fn(array $attributes) => [
            'move_type' => StockMoveType::Receipt,
        ]);
    }

    /**
     * Indicate that the move is a delivery.
     */
    public function delivery(): static
    {
        return $this->state(fn(array $attributes) => [
            'move_type' => StockMoveType::Delivery,
        ]);
    }

    /**
     * Indicate that the move is an adjustment.
     */
    public function adjustment(): static
    {
        return $this->state(fn(array $attributes) => [
            'move_type' => StockMoveType::Adjustment,
        ]);
    }

    /**
     * Indicate that the move is done.
     */
    public function done(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => StockMoveStatus::Done,
        ]);
    }

    /**
     * Indicate that the move is draft.
     */
    public function draft(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => StockMoveStatus::Draft,
        ]);
    }

    /**
     * Set specific quantity.
     */
    public function withQuantity(float $quantity): static
    {
        return $this->state(fn(array $attributes) => [
            'quantity' => $quantity,
        ]);
    }
}
