<?php

namespace Kezi\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Accounting\Models\Budget;

/**
 * @extends Factory<\Kezi\Accounting\Models\BudgetLine>
 */
class BudgetLineFactory extends Factory
{
    protected $model = \Kezi\Accounting\Models\BudgetLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'budget_id' => Budget::factory(),

            'budgeted_amount' => $this->faker->numberBetween(1000, 100000),
            'achieved_amount' => $this->faker->numberBetween(0, 100000),
            'committed_amount' => $this->faker->numberBetween(0, 100000),
        ];
    }
}
