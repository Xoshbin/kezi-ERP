<?php

namespace Modules\Payment\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Payment\Enums\Cheques\ChequeStatus;
use Modules\Payment\Enums\Cheques\ChequeType;
use Modules\Payment\Models\Cheque;

class ChequeFactory extends Factory
{
    protected $model = Cheque::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'journal_id' => Journal::factory(),
            'partner_id' => Partner::factory(),
            'currency_id' => Currency::factory()->createSafely(),
            'cheque_number' => $this->faker->unique()->numerify('CHQ-#####'),
            'amount' => 1000,
            'amount_company_currency' => 1000,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => ChequeStatus::Draft,
            'type' => ChequeType::Payable,
            'payee_name' => $this->faker->name(),
            'memo' => $this->faker->sentence(),
        ];
    }

    public function payable(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => ChequeType::Payable,
            ];
        });
    }

    public function receivable(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => ChequeType::Receivable,
            ];
        });
    }
}
