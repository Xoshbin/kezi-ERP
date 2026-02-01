<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kezi\HR\Models\ExpenseReport>
 */
class ExpenseReportFactory extends Factory
{
    protected $model = \Kezi\HR\Models\ExpenseReport::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'cash_advance_id' => \Kezi\HR\Models\CashAdvance::factory(),
            'employee_id' => fn (array $attributes) => \Kezi\HR\Models\CashAdvance::find($attributes['cash_advance_id'])->employee_id,
            'report_number' => $this->faker->unique()->numerify('EXP-#####'),
            'report_date' => now(),
            'total_amount' => \Brick\Money\Money::zero('USD'),
            'status' => \Kezi\HR\Enums\ExpenseReportStatus::Draft,
        ];
    }
}
