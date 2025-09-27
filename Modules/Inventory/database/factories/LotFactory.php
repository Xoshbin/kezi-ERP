<?php

namespace Modules\Inventory\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Product\Models\Product;

/**
 * @extends Factory<Lot>
 */
class LotFactory extends Factory
{
    protected $model = Lot::class;

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
            'lot_code' => 'LOT-' . $this->faker->unique()->numerify('####'),
            'expiration_date' => $this->faker->optional(0.7)->dateTimeBetween('+1 month', '+2 years'),
            'active' => true,
        ];
    }

    /**
     * Indicate that the lot is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiration_date' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    /**
     * Indicate that the lot has no expiration date.
     */
    public function noExpiration(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiration_date' => null,
        ]);
    }

    /**
     * Indicate that the lot is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
