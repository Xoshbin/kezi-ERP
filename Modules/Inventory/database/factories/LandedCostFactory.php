<?php

namespace Modules\Inventory\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Enums\Inventory\LandedCostAllocationMethod;
use Modules\Inventory\Enums\Inventory\LandedCostStatus;
use Modules\Inventory\Models\LandedCost;

class LandedCostFactory extends Factory
{
    protected $model = LandedCost::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'status' => LandedCostStatus::Draft,
            'date' => $this->faker->date(),
            'amount_total' => $this->faker->numberBetween(100, 10000), // Money cast handles the conversion usually, but assuming base currency input
            'description' => $this->faker->sentence,
            'allocation_method' => $this->faker->randomElement(LandedCostAllocationMethod::cases()),
        ];
    }
}
