<?php

namespace Jmeryar\ProjectManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\ProjectManagement\Enums\BillingType;
use Jmeryar\ProjectManagement\Enums\ProjectStatus;
use Jmeryar\ProjectManagement\Models\Project;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->bs,
            'code' => $this->faker->unique()->bothify('PRJ-####'),
            'description' => $this->faker->sentence,
            'status' => ProjectStatus::Draft, // Default
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'budget_amount' => $this->faker->randomFloat(2, 1000, 100000),
            'is_billable' => true,
            'billing_type' => BillingType::TimeAndMaterials,
            'hourly_rate' => $this->faker->randomFloat(2, 50, 500),
        ];
    }
}
