<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\Department;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'name' => ['en' => $this->faker->jobTitle],
            'description' => $this->faker->sentence,
            'is_active' => true,
        ];
    }
}
