<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kezi\HR\Models\CashAdvance>
 */
class CashAdvanceFactory extends Factory
{
    protected $model = \Kezi\HR\Models\CashAdvance::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'employee_id' => \Kezi\HR\Models\Employee::factory(),
            'currency_id' => fn (array $attributes) => \App\Models\Company::find($attributes['company_id'])->base_currency_id ?? \App\Models\Currency::factory(),
            'advance_number' => $this->faker->unique()->numerify('ADV-#####'),
            'requested_amount' => \Brick\Money\Money::of($this->faker->numberBetween(100, 5000), 'USD'),
            'purpose' => $this->faker->sentence(),
            'expected_return_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'status' => \Kezi\HR\Enums\CashAdvanceStatus::Draft,
            'requested_at' => now(),
        ];
    }
}
