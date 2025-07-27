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
            'company_id' => Company::factory(),
            'vendor_id' => Partner::factory(),
            'bill_date' => $this->faker->date(),
            'accounting_date' => $this->faker->date(),
            'due_date' => $this->faker->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'bill_reference' => $this->faker->unique()->bothify('BILL-####??'),
            'status' => 'draft',
            'total_amount' => 0,
            'total_tax' => 0,
            'journal_entry_id' => null,
            'posted_at' => null,
            'reset_to_draft_log' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function ($vendorBill) {
            if ($vendorBill->company) {
                $vendorBill->currency_id = $vendorBill->company->currency_id;
            }
        });
    }
}
