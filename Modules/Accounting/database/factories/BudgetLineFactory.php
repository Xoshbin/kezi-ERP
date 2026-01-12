<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\Budget;
use Modules\Accounting\Models\BudgetLine;

/**
 * @extends Factory<BudgetLine>
 */
class BudgetLineFactory extends Factory
{
    protected $model = \Modules\Accounting\Models\BudgetLine::class;

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
