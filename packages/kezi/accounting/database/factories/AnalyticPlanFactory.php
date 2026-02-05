<?php

namespace Kezi\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\Kezi\Accounting\Models\AnalyticPlan>
 */
class AnalyticPlanFactory extends Factory
{
    protected $model = \Kezi\Accounting\Models\AnalyticPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'name' => [
                'en' => $this->faker->words(2, true).' Plan',
                'ckb' => $this->faker->words(2, true).' پلان',
            ],
            'parent_id' => null,
            'color' => $this->faker->hexColor(),
            'default_applicability' => 'optional',
        ];
    }
}
