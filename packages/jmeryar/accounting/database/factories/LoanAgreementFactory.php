<?php

namespace Jmeryar\Accounting\Database\Factories;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\Accounting\Enums\Loans\LoanStatus;
use Jmeryar\Accounting\Enums\Loans\LoanType;
use Jmeryar\Accounting\Enums\Loans\ScheduleMethod;
use Jmeryar\Accounting\Models\LoanAgreement;
use Jmeryar\Foundation\Models\Currency;

/**
 * @extends Factory<LoanAgreement>
 */
class LoanAgreementFactory extends Factory
{
    protected $model = \Jmeryar\Accounting\Models\LoanAgreement::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'partner_id' => null,
            'name' => 'Test Loan',
            'loan_date' => now()->toDateString(),
            'start_date' => now()->addMonth()->startOfMonth()->toDateString(),
            'maturity_date' => now()->addMonths(12)->toDateString(),
            'duration_months' => 12,
            'currency_id' => Currency::factory()->createSafely(),
            'principal_amount' => Money::of('10000', 'USD'),
            'outstanding_principal' => Money::of('10000', 'USD'),
            'loan_type' => LoanType::Payable,
            'status' => LoanStatus::Draft,
            'schedule_method' => ScheduleMethod::Annuity,
            'interest_rate' => 12.0,
            'eir_enabled' => false,
            'eir_rate' => null,
        ];
    }
}
