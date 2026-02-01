<?php

namespace Kezi\Accounting\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Accounting\Enums\Accounting\WithholdingTaxApplicability;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\WithholdingTaxType;

/**
 * @extends Factory<WithholdingTaxType>
 */
class WithholdingTaxTypeFactory extends Factory
{
    protected $model = WithholdingTaxType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->word().' WHT',
            'rate' => $this->faker->randomFloat(4, 0.01, 0.25), // 1% to 25%
            'threshold_amount' => null,
            'applicable_to' => $this->faker->randomElement([
                WithholdingTaxApplicability::Services,
                WithholdingTaxApplicability::Goods,
                WithholdingTaxApplicability::Both,
            ]),
            'withholding_account_id' => Account::factory(),
            'is_active' => true,
        ];
    }

    /**
     * State for a 5% services WHT (common rate).
     */
    public function servicesAtFivePercent(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Services WHT 5%',
            'rate' => 0.05,
            'applicable_to' => WithholdingTaxApplicability::Services,
        ]);
    }

    /**
     * State for a 15% standard WHT (Iraq standard for non-residents).
     */
    public function standardFifteenPercent(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Standard WHT 15%',
            'rate' => 0.15,
            'applicable_to' => WithholdingTaxApplicability::Both,
        ]);
    }

    /**
     * State for WHT with a threshold amount.
     */
    public function withThreshold(int $thresholdMinorUnits): static
    {
        return $this->state(fn (array $attributes) => [
            'threshold_amount' => $thresholdMinorUnits,
        ]);
    }

    /**
     * State for inactive WHT type.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
