<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LockDate>
 */
class LockDateFactory extends Factory
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
            'lock_type' => $this->faker->randomElement(['tax_return_date', 'everything_date']),
            'locked_until' => $this->faker->dateTimeBetween('now', '+1 year'),
        ];
    }
}
