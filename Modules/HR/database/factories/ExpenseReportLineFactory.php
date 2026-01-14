<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\ExpenseReportLine;

class ExpenseReportLineFactory extends Factory
{
    protected $model = ExpenseReportLine::class;

    public function definition(): array
    {
        return [
            'expense_report_id' => \Modules\HR\Models\ExpenseReport::factory(),
            'expense_account_id' => \Modules\Accounting\Models\Account::factory(),
            'company_id' => \App\Models\Company::factory(),
            'description' => $this->faker->sentence,
            'expense_date' => now(),
            'amount' => 100,
            'receipt_reference' => $this->faker->bothify('REC-####'),
            'partner_id' => null,
        ];
    }
}
