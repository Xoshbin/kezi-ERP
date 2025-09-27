<?php

namespace Modules\Payment\Database\Factories;

use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\PaymentType;
use App\Models\Company;
use App\Models\Journal;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'journal_id' => Journal::factory(),
            'currency_id' => \Modules\Foundation\Models\Currency::factory()->createSafely(),
            'paid_to_from_partner_id' => \Modules\Foundation\Models\Partner::factory(),
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
