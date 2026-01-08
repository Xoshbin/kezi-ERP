<?php

namespace Modules\Payment\Database\Factories;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Payment\Enums\LetterOfCredit\LCStatus;
use Modules\Payment\Enums\LetterOfCredit\LCType;
use Modules\Payment\Models\LetterOfCredit;

class LetterOfCreditFactory extends Factory
{
    protected $model = LetterOfCredit::class;

    public function definition(): array
    {
        $amount = Money::of(fake()->numberBetween(50000, 500000), 'IQD');

        return [
            'company_id' => Company::factory(),
            'vendor_id' => Partner::factory(),
            'issuing_bank_partner_id' => null,
            'currency_id' => Currency::firstOrCreate(
                ['code' => 'IQD'],
                ['name' => 'Iraqi Dinar', 'symbol' => 'IQD']
            )->id,
            'purchase_order_id' => null,
            'created_by_user_id' => User::factory(),
            'lc_number' => 'LC-'.fake()->year().'-'.fake()->unique()->numberBetween(1000, 9999),
            'bank_reference' => null,
            'type' => fake()->randomElement(LCType::cases())->value,
            'status' => LCStatus::Draft->value,
            'amount' => $amount,
            'amount_company_currency' => $amount,
            'utilized_amount' => Money::of(0, 'IQD'),
            'balance' => $amount,
            'issue_date' => now(),
            'expiry_date' => now()->addMonths(3),
            'shipment_date' => null,
            'incoterm' => fake()->randomElement(['FOB', 'CIF', 'DDP', null]),
            'terms_and_conditions' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function issued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LCStatus::Issued->value,
            'bank_reference' => 'BANK-'.fake()->unique()->numberBetween(10000, 99999),
        ]);
    }

    public function partiallyUtilized(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $attributes['amount'];
            $utilized = $amount->multipliedBy('0.5');

            return [
                'status' => LCStatus::PartiallyUtilized->value,
                'utilized_amount' => $utilized,
                'balance' => $amount->minus($utilized),
            ];
        });
    }
}
