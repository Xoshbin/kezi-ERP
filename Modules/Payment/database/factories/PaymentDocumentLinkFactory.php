<?php

namespace Modules\Payment\Database\Factories;

use App\Models\PaymentDocumentLink;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentDocumentLink>
 */
class PaymentDocumentLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $payment = \Modules\Payment\Models\Payment::factory();

        return [
            'payment_id' => $payment,
            'company_id' => function (array $attributes) {
                return \Modules\Payment\Models\Payment::find($attributes['payment_id'])->company_id;
            },
            'invoice_id' => \Modules\Sales\Models\Invoice::factory(),
            'vendor_bill_id' => null, // Either invoice_id or vendor_bill_id should be set, not both
            'amount_applied' => Money::of($this->faker->randomFloat(2, 100, 1000), 'USD'),
        ];
    }

    /**
     * Create a link for a vendor bill instead of an invoice.
     */
    public function forVendorBill(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'invoice_id' => null,
                'vendor_bill_id' => \Modules\Purchase\Models\VendorBill::factory(),
            ];
        });
    }
}
