<?php

namespace Modules\Sales\Database\Factories;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\Account;
use Modules\Sales\Models\Invoice;

class InvoiceLineFactory extends Factory
{
    protected $model = \Modules\Sales\Models\InvoiceLine::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 5);
        $unitPrice = Money::of($this->faker->randomFloat(2, 25, 500), 'USD');
        $subtotal = $unitPrice->multipliedBy($quantity);

        return [
            // A line should NOT create its own parent invoice. The test should provide it.
            'invoice_id' => Invoice::factory(),
            'company_id' => function (array $attributes) {
                return Invoice::find($attributes['invoice_id'])->company_id;
            },
            'product_id' => null, // Default to a descriptive line without a product
            'description' => $this->faker->sentence(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_id' => null, // Default to no tax
            'income_account_id' => function (array $attributes) {
                $invoice = Invoice::find($attributes['invoice_id']);

                return Account::factory()->create([
                    'type' => 'income',
                    'company_id' => $invoice->company_id,
                    'currency_id' => $invoice->currency_id,
                ])->id;
            },
            // Calculate subtotal and tax properly
            'subtotal' => $subtotal,
            'total_line_tax' => Money::of(0, 'USD'), // No tax by default
        ];
    }
}
