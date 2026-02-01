<?php

namespace Jmeryar\ProjectManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\ProjectManagement\Models\TimesheetLine;

class TimesheetLineFactory extends Factory
{
    protected $model = TimesheetLine::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'date' => now(),
            'hours' => $this->faker->randomFloat(2, 1, 8),
            'description' => $this->faker->sentence,
            'is_billable' => true,
        ];
    }
}
