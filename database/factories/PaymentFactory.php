<?php

namespace Database\Factories;

use Brick\Money\Money;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'journal_id' => Journal::factory(),
            'currency_id' => Currency::factory(),
            'paid_to_from_partner_id' => Partner::factory(),
            'payment_date' => $this->faker->date(),
            'amount' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
            // Use model constants for clarity and maintainability.
            'payment_type' => $this->faker->randomElement([Payment::TYPE_INBOUND, Payment::TYPE_OUTBOUND]),
            'reference' => $this->faker->sentence(3),
            'status' => Payment::STATUS_DRAFT,
            'journal_entry_id' => null,
        ];
    }
}
