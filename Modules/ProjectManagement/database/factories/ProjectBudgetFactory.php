<?php

namespace Modules\ProjectManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ProjectManagement\Models\ProjectBudget;

class ProjectBudgetFactory extends Factory
{
    protected $model = ProjectBudget::class;

    public function definition(): array
    {
        return [
            'name' => 'Project Budget '.$this->faker->year,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'total_budget' => $this->faker->randomFloat(2, 50000, 500000),
            'is_active' => true,
        ];
    }
}
