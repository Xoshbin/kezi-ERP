<?php

namespace Modules\ProjectManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ProjectManagement\Models\ProjectBudgetLine;

class ProjectBudgetLineFactory extends Factory
{
    protected $model = ProjectBudgetLine::class;

    public function definition(): array
    {
        return [
            'description' => $this->faker->words(3, true),
            'budgeted_amount' => $this->faker->randomFloat(2, 1000, 50000),
            'actual_amount' => 0,
        ];
    }
}
