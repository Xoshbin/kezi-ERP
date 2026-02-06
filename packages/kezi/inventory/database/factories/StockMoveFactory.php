<?php

namespace Kezi\Inventory\Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockMove;

/**
 * @extends Factory<\Kezi\Inventory\Models\StockMove>
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
            'move_type' => $this->faker->randomElement(StockMoveType::cases()),
            'status' => $this->faker->randomElement(StockMoveStatus::cases()),
            'move_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'reference' => 'SM-'.$this->faker->unique()->numerify('####'),
            'description' => $this->faker->sentence(),
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
        return $this->state(fn (array $attributes) => [
            'move_type' => StockMoveType::Incoming,
        ]);
    }

    /**
     * Indicate that the move is a delivery.
     */
    public function delivery(): static
    {
        return $this->state(fn (array $attributes) => [
            'move_type' => StockMoveType::Outgoing,
        ]);
    }

    /**
     * Indicate that the move is an adjustment.
     */
    public function adjustment(): static
    {
        return $this->state(fn (array $attributes) => [
            'move_type' => StockMoveType::Adjustment,
        ]);
    }

    /**
     * Indicate that the move is done.
     */
    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockMoveStatus::Done,
        ]);
    }

    /**
     * Indicate that the move is draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockMoveStatus::Draft,
        ]);
    }

    /**
     * Set specific quantity.
     */
    public function withQuantity(float $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }

    /**
     * Create a model instance with backward compatibility for old-style parameters
     */
    public function create($attributes = [], ?Model $parent = null)
    {
        // Extract old-style parameters if present
        $oldStyleParams = null;
        if (is_array($attributes) && isset($attributes['product_id'])) {
            $oldStyleParams = [
                'product_id' => $attributes['product_id'],
                'quantity' => $attributes['quantity'] ?? 1,
                'from_location_id' => $attributes['from_location_id'] ?? null,
                'to_location_id' => $attributes['to_location_id'] ?? null,
            ];

            // Remove old-style parameters from attributes to avoid database errors
            unset($attributes['product_id'], $attributes['quantity'], $attributes['from_location_id'], $attributes['to_location_id']);
        }

        // Create the stock move without old-style parameters
        $stockMove = parent::create($attributes, $parent);

        // If we had old-style parameters, create a product line
        if ($oldStyleParams) {
            // Ensure we have valid location IDs
            $company = $stockMove->company;
            $fromLocationId = $oldStyleParams['from_location_id'] ?? $company->vendorLocation?->id ?? StockLocation::factory()->create(['company_id' => $company->id])->id;
            $toLocationId = $oldStyleParams['to_location_id'] ?? $company->defaultStockLocation?->id ?? StockLocation::factory()->create(['company_id' => $company->id])->id;

            $stockMove->productLines()->create([
                'company_id' => $stockMove->company_id,
                'product_id' => $oldStyleParams['product_id'],
                'quantity' => $oldStyleParams['quantity'],
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocationId,
                'description' => null,
                'source_type' => $stockMove->source_type,
                'source_id' => $stockMove->source_id,
            ]);
        }

        return $stockMove;
    }
}
