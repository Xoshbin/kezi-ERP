<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Budget>
 */
class BudgetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->word,
            'period_start_date' => $this->faker->date(),
            'period_end_date' => $this->faker->dateTimeBetween('+1 month', '+1 year'),
            'budget_type' => $this->faker->randomElement(['analytic', 'financial']),
            'status' => $this->faker->randomElement(['draft', 'finalized']),
            'currency_id' => function (array $attributes) {
                return \App\Models\Company::find($attributes['company_id'])->currency_id;
            },
        ];
    }
}
