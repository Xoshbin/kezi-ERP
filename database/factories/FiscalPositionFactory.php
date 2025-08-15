<?php

namespace Database\Factories;

use App\Models\FiscalPosition;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FiscalPosition>
 */
class FiscalPositionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory()->create()->id,
            'name' => $this->faker->company,
            'country' => $this->faker->country,
        ];
    }
}
