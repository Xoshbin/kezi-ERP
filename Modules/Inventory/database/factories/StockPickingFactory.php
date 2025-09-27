<?php

namespace Modules\Inventory\Database\Factories;

use App\Enums\Inventory\StockPickingState;
use App\Enums\Inventory\StockPickingType;
use App\Models\Company;
use App\Models\Partner;
use App\Models\StockPicking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockPicking>
 */
class StockPickingFactory extends Factory
{
    protected $model = StockPicking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => $this->faker->randomElement(StockPickingType::cases()),
            'state' => $this->faker->randomElement(StockPickingState::cases()),
            'partner_id' => null,
            'scheduled_date' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'completed_at' => null,
            'reference' => 'SP-' . $this->faker->unique()->numerify('####'),
            'origin' => null,
            'created_by_user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the picking is a receipt.
     */
    public function receipt(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockPickingType::Receipt,
        ]);
    }

    /**
     * Indicate that the picking is a delivery.
     */
    public function delivery(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockPickingType::Delivery,
        ]);
    }

    /**
     * Indicate that the picking is an internal transfer.
     */
    public function internal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockPickingType::Internal,
        ]);
    }

    /**
     * Indicate that the picking is done.
     */
    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => StockPickingState::Done,
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the picking is draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => StockPickingState::Draft,
            'completed_at' => null,
        ]);
    }

    /**
     * Set a specific partner.
     */
    public function withPartner(): static
    {
        return $this->state(fn (array $attributes) => [
            'partner_id' => \Modules\Foundation\Models\Partner::factory(),
        ]);
    }
}
