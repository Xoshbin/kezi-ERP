<?php

namespace Modules\Inventory\Database\Factories;

use App\Enums\Inventory\StockLocationType;
use App\Models\Company;
use App\Models\StockLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

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
