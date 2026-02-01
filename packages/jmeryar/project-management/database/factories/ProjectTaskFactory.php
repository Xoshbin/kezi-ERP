<?php

namespace Jmeryar\ProjectManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\ProjectManagement\Enums\TaskStatus;
use Jmeryar\ProjectManagement\Models\ProjectTask;

class ProjectTaskFactory extends Factory
{
    protected $model = ProjectTask::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'project_id' => \Jmeryar\ProjectManagement\Models\Project::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'status' => TaskStatus::Pending,
            'estimated_hours' => $this->faker->randomFloat(2, 1, 40),
            'sequence' => $this->faker->numberBetween(1, 10),
        ];
    }
}
