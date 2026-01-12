<?php

namespace Modules\Accounting\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Enums\Budgets\BudgetStatus;
use Modules\Accounting\Enums\Budgets\BudgetType;
use Modules\Foundation\Models\Currency;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    protected $model = \Modules\Accounting\Models\Budget::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->word,
            'period_start_date' => $this->faker->date(),
            'period_end_date' => $this->faker->dateTimeBetween('+1 month', '+1 year'),
            'budget_type' => $this->faker->randomElement([BudgetType::Analytic, BudgetType::Financial]),
            'status' => $this->faker->randomElement([BudgetStatus::Draft, BudgetStatus::Finalized]),
            'currency_id' => Currency::factory()->createSafely()->id,
        ];
    }
}
