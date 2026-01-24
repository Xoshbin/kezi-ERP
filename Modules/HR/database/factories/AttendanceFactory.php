<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\Attendance;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'employee_id' => \Modules\HR\Models\Employee::factory(),
            'attendance_date' => $this->faker->date(),
            'clock_in_time' => $this->faker->dateTime(),
            'clock_out_time' => $this->faker->dateTime(),
            'status' => 'present',
            'is_manual_entry' => false,
            'total_hours' => 8,
        ];
    }
}
