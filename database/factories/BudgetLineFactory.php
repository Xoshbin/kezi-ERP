<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BudgetLine>
 */
class BudgetLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'budget_id' => \App\Models\Budget::factory(),
            'budgeted_amount' => $this->faker->numberBetween(1000, 100000),
            'achieved_amount' => $this->faker->numberBetween(0, 100000),
            'committed_amount' => $this->faker->numberBetween(0, 100000),
        ];
    }
}
