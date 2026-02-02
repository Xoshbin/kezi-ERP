<?php

namespace Kezi\ProjectManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\ProjectManagement\Models\ProjectBudgetLine;

class ProjectBudgetLineFactory extends Factory
{
    protected $model = ProjectBudgetLine::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'project_budget_id' => \Kezi\ProjectManagement\Models\ProjectBudget::factory(),
            'account_id' => \Kezi\Accounting\Models\Account::factory(),
            'description' => $this->faker->words(3, true),
            'budgeted_amount' => $this->faker->randomFloat(2, 1000, 50000),
            'actual_amount' => 0,
        ];
    }
}
