<?php

namespace Kezi\HR\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Foundation\Models\Currency;
use Kezi\HR\Models\Employee;
use Kezi\HR\Models\Payroll;

/**
 * @extends Factory<\Kezi\Hr\Models\Payroll>
 */
class PayrollFactory extends Factory
{
    protected $model = Payroll::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'currency_id' => Currency::factory()->createSafely(),
            'payroll_number' => $this->faker->unique()->numerify('PAY-#####'),
            'period_start_date' => $this->faker->dateTimeThisYear,
            'period_end_date' => $this->faker->dateTimeThisYear,
            'pay_date' => $this->faker->dateTimeThisYear,
            'pay_frequency' => 'monthly',
            'base_salary' => 1000,
            'overtime_amount' => 0,
            'housing_allowance' => 0,
            'transport_allowance' => 0,
            'meal_allowance' => 0,
            'other_allowances' => 0,
            'bonus' => 0,
            'commission' => 0,
            'gross_salary' => 1000,
            'income_tax' => 0,
            'social_security' => 0,
            'health_insurance' => 0,
            'pension_contribution' => 0,
            'other_deductions' => 0,
            'total_deductions' => 0,
            'net_salary' => 1000,
            'regular_hours' => 160,
            'overtime_hours' => 0,
            'total_hours' => 160,
            'status' => 'draft',
            'processed_by_user_id' => null,
            'processed_at' => null,
            'approved_by_user_id' => null,
            'approved_at' => null,
            'notes' => $this->faker->sentence,
            'adjustments' => null,
        ];
    }
}
