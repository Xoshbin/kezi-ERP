<?php

namespace Kezi\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\HR\Models\CashAdvance;

class CashAdvanceFactory extends Factory
{
    protected $model = CashAdvance::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'employee_id' => \Kezi\HR\Models\Employee::factory(),
            'currency_id' => \Kezi\Foundation\Models\Currency::factory()->createSafely(),
            'advance_number' => $this->faker->unique()->bothify('ADV-####'),
            'requested_amount' => 1000,
            'approved_amount' => null,
            'disbursed_amount' => null,
            'purpose' => $this->faker->sentence,
            'expected_return_date' => $this->faker->date(),
            'status' => \Kezi\HR\Enums\CashAdvanceStatus::Draft,
            'requested_at' => now(),
            'notes' => $this->faker->paragraph,
        ];
    }
}
