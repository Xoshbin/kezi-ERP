<?php

namespace Modules\Inventory\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Product\Models\Product;

/**
 * @extends Factory<StockMoveProductLine>
 */
class StockMoveProductLineFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StockMoveProductLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'stock_move_id' => StockMove::factory(),
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(1, 100),
            'from_location_id' => StockLocation::factory(),
            'to_location_id' => StockLocation::factory(),
            'description' => $this->faker->sentence(),
            'source_type' => 'Test',
            'source_id' => 1,
        ];
    }
}
