<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Jmeryar\HR\Models\ExpenseReportLine>
 */
class ExpenseReportLineFactory extends Factory
{
    protected $model = \Jmeryar\HR\Models\ExpenseReportLine::class;

    public function definition(): array
    {
        return [
            'expense_report_id' => \Jmeryar\HR\Models\ExpenseReport::factory(),
            'expense_account_id' => \Jmeryar\Accounting\Models\Account::factory(),
            'description' => $this->faker->sentence(),
            'expense_date' => now(),
            'amount' => \Brick\Money\Money::of($this->faker->numberBetween(10, 500), 'USD'),
            'receipt_reference' => $this->faker->bothify('REC-####'),
        ];
    }
}
