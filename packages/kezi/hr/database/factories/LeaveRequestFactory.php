<?php

namespace Kezi\HR\Database\Factories;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\HR\Models\Employee;
use Kezi\HR\Models\LeaveRequest;
use Kezi\HR\Models\LeaveType;

class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $startDate = Carbon::instance($this->faker->dateTimeBetween('+1 day', '+1 month'));
        $endDate = $startDate->copy()->addDays($this->faker->numberBetween(1, 5));
        $daysRequested = $startDate->diffInDays($endDate) + 1; // Inclusive

        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'request_number' => 'LR-'.$this->faker->unique()->numberBetween(1000, 9999),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'days_requested' => $daysRequested,
            'reason' => $this->faker->sentence,
            'notes' => $this->faker->optional()->paragraph,
            'status' => 'pending',
            'requested_by_user_id' => User::factory(),
            'submitted_at' => now(),
        ];
    }
}
