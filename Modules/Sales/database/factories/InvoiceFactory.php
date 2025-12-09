<?php

namespace Modules\Sales\Database\Factories;

use Brick\Money\Money;
use App\Models\Company;
use Modules\Sales\Models\Invoice;

use Modules\Sales\Models\InvoiceLine;
use Modules\Foundation\Models\Partner;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = \Modules\Sales\Models\Invoice::class;

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
            'status' => InvoiceStatus::Draft,
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
                'status' => InvoiceStatus::Posted,
                'posted_at' => now(),
            ];
        });
    }

    public function withLines(int $count = 1): self
    {
        return $this->afterCreating(function (Invoice $invoice) use ($count) {
            InvoiceLine::factory()->count($count)->create([
                'invoice_id' => $invoice->id,
            ]);
        });
    }
}
