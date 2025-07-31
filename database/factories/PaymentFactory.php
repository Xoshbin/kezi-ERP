<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory()->create()->id,
            'journal_id' => Journal::factory()->create()->id,
            'payment_date' => $this->faker->date(),
            'amount' => $this->faker->randomFloat(2, 10, 10000),
            'currency_id' => function (array $attributes) {
                return Company::find($attributes['company_id'])->currency_id;
            },
            'payment_type' => $this->faker->randomElement(['cash', 'bank', 'cheque', 'online']),
            'reference' => $this->faker->uuid(),
            'status' => Payment::STATUS_DRAFT,
            'paid_to_from_partner_id' => Partner::factory()->create()->id,
            'journal_entry_id' => null,
        ];
    }
}
