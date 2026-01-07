<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\ExpenseReport;

class ExpenseReportFactory extends Factory
{
    protected $model = ExpenseReport::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'cash_advance_id' => \Modules\HR\Models\CashAdvance::factory(),
            'employee_id' => \Modules\HR\Models\Employee::factory(),
            'report_number' => $this->faker->unique()->bothify('ER-####'),
            'report_date' => now(),
            'total_amount' => 1000, // Should be sum of lines usually
            'status' => \Modules\HR\Enums\ExpenseReportStatus::Draft,
            'notes' => $this->faker->paragraph,
        ];
    }
}
