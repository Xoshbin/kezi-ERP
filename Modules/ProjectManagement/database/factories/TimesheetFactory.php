<?php

namespace Modules\ProjectManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ProjectManagement\Enums\TimesheetStatus;
use Modules\ProjectManagement\Models\Timesheet;

class TimesheetFactory extends Factory
{
    protected $model = Timesheet::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'employee_id' => \Modules\HR\Models\Employee::factory(),
            'start_date' => now()->startOfWeek(),
            'end_date' => now()->endOfWeek(),
            'status' => TimesheetStatus::Draft,
            'total_hours' => 0,
        ];
    }
}
