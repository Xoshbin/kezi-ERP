<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Journal;
use App\Models\Partner;
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
            'currency_id' => Currency::factory()->create()->id,
            'payment_type' => $this->faker->randomElement(['cash', 'bank', 'cheque', 'online']),
            'reference' => $this->faker->uuid(),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'paid_to_from_partner_id' => Partner::factory()->create()->id,
            'journal_entry_id' => $this->faker->numberBetween(1, 100),
        ];
    }
}
