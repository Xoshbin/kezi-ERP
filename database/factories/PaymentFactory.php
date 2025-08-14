<?php

namespace Database\Factories;

use Brick\Money\Money;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Currency;
use App\Enums\Payments\PaymentType;
use App\Enums\Payments\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'journal_id' => Journal::factory(),
            'currency_id' => Currency::firstOrCreate(
                ['code' => 'IQD'],
                [
                    'name' => 'Iraqi Dinar',
                    'symbol' => 'IQD',
                    'exchange_rate' => 1.0,
                    'is_active' => true,
                    'decimal_places' => 3
                ]
            )->id,
            'paid_to_from_partner_id' => Partner::factory(),
            'payment_date' => $this->faker->date(),
            'amount' => Money::of($this->faker->randomFloat(2, 100, 10000), 'IQD'),
            // Use enum values for clarity and maintainability.
            'payment_type' => $this->faker->randomElement([PaymentType::Inbound, PaymentType::Outbound]),
            'reference' => $this->faker->sentence(3),
            'status' => PaymentStatus::Draft,
            'journal_entry_id' => null,
        ];
    }
}
