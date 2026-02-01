<?php

namespace Jmeryar\Accounting\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\Accounting\Models\WithholdingTaxEntry;
use Jmeryar\Accounting\Models\WithholdingTaxType;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Payment\Models\Payment;

/**
 * @extends Factory<WithholdingTaxEntry>
 */
class WithholdingTaxEntryFactory extends Factory
{
    protected $model = WithholdingTaxEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $baseAmount = $this->faker->numberBetween(100000, 10000000); // Minor units
        $rate = $this->faker->randomFloat(4, 0.01, 0.25);
        $withheldAmount = (int) ($baseAmount * $rate);

        return [
            'company_id' => Company::factory(),
            'payment_id' => Payment::factory(),
            'withholding_tax_type_id' => WithholdingTaxType::factory(),
            'vendor_id' => Partner::factory(),
            'base_amount' => $baseAmount,
            'withheld_amount' => $withheldAmount,
            'rate_applied' => $rate,
            'currency_id' => Currency::factory(),
            'withholding_tax_certificate_id' => null,
        ];
    }

    /**
     * State for a certified entry.
     */
    public function certified(): static
    {
        return $this->state(fn (array $attributes) => [
            'withholding_tax_certificate_id' => \Jmeryar\Accounting\Models\WithholdingTaxCertificate::factory(),
        ]);
    }
}
