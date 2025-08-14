<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Enums\Budgets\BudgetType;
use App\Enums\Budgets\BudgetStatus;
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
            'budget_type' => $this->faker->randomElement([BudgetType::Analytic, BudgetType::Financial]),
            'status' => $this->faker->randomElement([BudgetStatus::Draft, BudgetStatus::Finalized]),
            'currency_id' => Currency::firstOrCreate(
                ['code' => 'IQD'],
                [
                    'name' => 'Iraqi Dinar',
                    'symbol' => 'IQD',
                    'exchange_rate' => 1.0,
                    'is_active' => true,
                    'decimal_places' => 3
                ]
            )->id,
        ];
    }
}
