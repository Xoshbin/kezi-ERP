<?php

namespace Kezi\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kezi\HR\Models\DeductionRule>
 */
class DeductionRuleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = \Kezi\HR\Models\DeductionRule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'code' => $this->faker->unique()->slug(),
            'type' => 'percentage',
            'value' => $this->faker->randomFloat(4, 0, 0.2), // 0% to 20%
            'amount' => null,
            'currency_code' => 'USD',
            'is_statutory' => $this->faker->boolean(),
            'is_active' => true,
            'liability_account_id' => null, // Or create a factory
        ];
    }
}
