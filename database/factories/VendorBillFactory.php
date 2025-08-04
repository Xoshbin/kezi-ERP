<?php

namespace Database\Factories;

use Brick\Money\Money;
use App\Models\Company;
use App\Models\Partner;
use App\Models\Currency;
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
            'company_id' => Company::factory(),
            'vendor_id' => Partner::factory(),
            'currency_id' => function (array $attributes) {
                return Company::find($attributes['company_id'])->currency_id;
            },
            'bill_date' => $this->faker->date(),
            'accounting_date' => $this->faker->date(),
            'due_date' => $this->faker->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'bill_reference' => $this->faker->unique()->bothify('BILL-####??'),
            'status' => 'draft',
            'total_amount' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
            'total_tax' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
            'journal_entry_id' => null,
            'posted_at' => null,
            'reset_to_draft_log' => null,
        ];
    }

}
