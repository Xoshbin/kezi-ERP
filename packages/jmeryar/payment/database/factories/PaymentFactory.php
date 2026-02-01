<?php

namespace Jmeryar\Payment\Database\Factories;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\Accounting\Models\Journal;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Payment\Enums\Payments\PaymentStatus;
use Jmeryar\Payment\Enums\Payments\PaymentType;

class PaymentFactory extends Factory
{
    protected $model = \Jmeryar\Payment\Models\Payment::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'journal_id' => Journal::factory(),
            'currency_id' => Currency::factory()->createSafely(),
            'paid_to_from_partner_id' => Partner::factory(),
            'payment_date' => $this->faker->date(),
            'amount' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
            // Use enum values for clarity and maintainability.
            'payment_type' => $this->faker->randomElement([PaymentType::Inbound, PaymentType::Outbound]),
            'reference' => $this->faker->sentence(3),
            'status' => PaymentStatus::Draft,
            'journal_entry_id' => null,
        ];
    }
}
