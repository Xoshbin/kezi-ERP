<?php

namespace Modules\HR\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\LeaveType;

class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => [
                'en' => $this->faker->words(2, true),
            ],
            'code' => $this->faker->unique()->bothify('LT###'),
            'description' => $this->faker->sentence,
            'default_days_per_year' => $this->faker->numberBetween(10, 30),
            'requires_approval' => true,
            'is_paid' => true,
            'carries_forward' => false,
            'color' => $this->faker->hexColor,
            'is_active' => true,
        ];
    }
}
