<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VendorBill>
 */
class VendorBillFactory extends Factory
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
            'vendor_id' => Partner::factory()->create()->id,
            'bill_date' => $this->faker->date(),
            'accounting_date' => $this->faker->date(),
            'due_date' => $this->faker->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'bill_reference' => $this->faker->unique()->bothify('BILL-####??'), // Assigned only upon 'confirmation' or 'posting'
            'status' => $this->faker->randomElement(['Draft', 'Posted', 'Paid', 'Cancelled']),
            'currency_id' => Currency::factory()->create()->id,
            'total_amount' => $this->faker->randomFloat(2, 100, 10000),
            'total_tax' => $this->faker->randomFloat(2, 0, 2000),
            'journal_entry_id' => null, // Nullable, set when posted
            'posted_at' => null, // Nullable, set when posted
            'reset_to_draft_log' => null, // Nullable, can be JSON or text
        ];
    }
}
