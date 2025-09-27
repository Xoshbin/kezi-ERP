<?php

namespace Database\Factories;

use App\Enums\Budgets\BudgetStatus;
use App\Enums\Budgets\BudgetType;
use App\Models\Budget;
use App\Models\Company;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
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
            'company_id' => Company::factory(),
            'name' => $this->faker->word,
            'period_start_date' => $this->faker->date(),
            'period_end_date' => $this->faker->dateTimeBetween('+1 month', '+1 year'),
            'budget_type' => $this->faker->randomElement([BudgetType::Analytic, BudgetType::Financial]),
            'status' => $this->faker->randomElement([BudgetStatus::Draft, BudgetStatus::Finalized]),
            'currency_id' => \Modules\Foundation\Models\Currency::factory()->createSafely()->id,
        ];
    }
}
