<?php

namespace Jmeryar\Purchase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Jmeryar\Purchase\Models\RequestForQuotation>
 */
class RequestForQuotationFactory extends Factory
{
    protected $model = \Jmeryar\Purchase\Models\RequestForQuotation::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'vendor_id' => \Jmeryar\Foundation\Models\Partner::factory()->vendor(),
            'currency_id' => \Jmeryar\Foundation\Models\Currency::factory()->createSafely(),
            'created_by_user_id' => \App\Models\User::factory(),
            'rfq_number' => 'RFQ-'.$this->faker->unique()->numberBetween(1000, 9999),
            'rfq_date' => now(),
            'valid_until' => now()->addDays(30),
            'exchange_rate' => 1,
            'status' => \Jmeryar\Purchase\Enums\Purchases\RequestForQuotationStatus::Draft,
            'notes' => $this->faker->sentence,
            'subtotal' => \Brick\Money\Money::of(0, 'USD'),
            'tax_total' => \Brick\Money\Money::of(0, 'USD'),
            'total' => \Brick\Money\Money::of(0, 'USD'),
        ];
    }
}
