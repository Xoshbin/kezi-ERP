<?php

namespace Jmeryar\HR\Database\Factories;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\HR\Models\Employee;
use Jmeryar\HR\Models\EmploymentContract;

/**
 * @extends Factory<EmploymentContract>
 */
class EmploymentContractFactory extends Factory
{
    protected $model = EmploymentContract::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currency = Currency::firstOrCreate(
            ['code' => 'IQD'],
            [
                'name' => 'Iraqi Dinar',
                'symbol' => 'IQD',
                'is_active' => true,
                'decimal_places' => 3,
            ]
        );

        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'currency_id' => $currency->id,
            'contract_number' => $this->faker->unique()->numerify('CON####'),
            'contract_type' => $type = $this->faker->randomElement(['permanent', 'fixed_term', 'temporary', 'consultancy', 'internship']),
            'start_date' => $this->faker->date('Y-m-d', 'now'),
            'end_date' => $type === 'permanent' ? null : $this->faker->date('Y-m-d', '+1 year'),
            'is_active' => true,
            'base_salary' => Money::of($this->faker->numberBetween(500000, 2000000), $currency->code),
            'hourly_rate' => null,
            'pay_frequency' => $this->faker->randomElement(['monthly', 'bi_weekly', 'weekly']),
            'housing_allowance' => Money::of($this->faker->numberBetween(100000, 500000), $currency->code),
            'transport_allowance' => Money::of($this->faker->numberBetween(50000, 200000), $currency->code),
            'meal_allowance' => Money::of($this->faker->numberBetween(25000, 100000), $currency->code),
            'other_allowances' => Money::of(0, $currency->code),
            'working_hours_per_week' => 40.0,
            'working_days_per_week' => 5.0,
            'annual_leave_days' => $this->faker->numberBetween(20, 30),
            'sick_leave_days' => $this->faker->numberBetween(10, 20),
            'maternity_leave_days' => 90,
            'paternity_leave_days' => 7,
            'probation_period_months' => $this->faker->randomElement([0, 3, 6]),
            'probation_end_date' => null,
            'notice_period_days' => $this->faker->numberBetween(15, 60),
            'terms_and_conditions' => $this->faker->paragraph,
            'job_description' => $this->faker->paragraph,
        ];
    }

    public function temporary(): static
    {
        return $this->state(fn (array $attributes) => [
            'contract_type' => 'temporary',
            'end_date' => $this->faker->date('Y-m-d', '+2 years'),
        ]);
    }

    public function withProbation(): static
    {
        return $this->state(fn (array $attributes) => [
            'probation_period_months' => 3,
            'probation_end_date' => $this->faker->date('Y-m-d', '+3 months'),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'end_date' => $this->faker->date('Y-m-d', 'now'),
        ]);
    }
}
