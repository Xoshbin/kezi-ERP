<?php

namespace Modules\Inventory\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Enums\Inventory\StockLocationType;
use Modules\Inventory\Models\StockLocation;

/**
 * @extends Factory<StockLocation>
 */
class StockLocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StockLocation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->words(2, true),
            'type' => StockLocationType::Internal,
            'is_active' => true,
            'parent_id' => null,
        ];
    }
}
