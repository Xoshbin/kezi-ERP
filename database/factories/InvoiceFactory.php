<?php

namespace Database\Factories;

use Brick\Money\Money;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            // Use factory instances for relationships. The test will provide these.
            'company_id' => Company::factory(),
            'customer_id' => Partner::factory()->state(['type' => 'customer']),
            'currency_id' => function (array $attributes) {
                return Company::find($attributes['company_id'])->currency_id;
            },
            'invoice_date' => $this->faker->date(),
            'due_date' => $this->faker->dateTimeBetween('+15 days', '+60 days')->format('Y-m-d'),
            'status' => Invoice::STATUS_DRAFT,
            // Totals should be 0 by default and calculated by observers when lines are added.
            'total_amount' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
            'total_tax' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
        ];
    }

    /**
     * Indicate that the invoice is posted.
     */
    public function posted(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Invoice::STATUS_POSTED,
                'posted_at' => now(),
            ];
        });
    }
}
