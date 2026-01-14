<?php

namespace Modules\Accounting\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Enums\Accounting\WithholdingTaxCertificateStatus;
use Modules\Accounting\Models\WithholdingTaxCertificate;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;

/**
 * @extends Factory<WithholdingTaxCertificate>
 */
class WithholdingTaxCertificateFactory extends Factory
{
    protected $model = WithholdingTaxCertificate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = $this->faker->dateTimeBetween('-3 months', '-1 month');
        $periodEnd = $this->faker->dateTimeBetween($periodStart, 'now');

        return [
            'company_id' => Company::factory(),
            'certificate_number' => 'WHT-'.strtoupper($this->faker->unique()->bothify('????####')),
            'vendor_id' => Partner::factory(),
            'certificate_date' => $this->faker->dateTimeBetween($periodEnd, 'now'),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_base_amount' => $this->faker->numberBetween(500000, 50000000),
            'total_withheld_amount' => $this->faker->numberBetween(25000, 2500000),
            'currency_id' => Currency::factory(),
            'status' => WithholdingTaxCertificateStatus::Draft,
            'notes' => null,
        ];
    }

    /**
     * State for an issued certificate.
     */
    public function issued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WithholdingTaxCertificateStatus::Issued,
        ]);
    }

    /**
     * State for a cancelled certificate.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WithholdingTaxCertificateStatus::Cancelled,
        ]);
    }
}
