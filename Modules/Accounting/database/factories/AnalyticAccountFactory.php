<?php

namespace Modules\Accounting\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\AnalyticAccount;
use Modules\Foundation\Models\Currency;

/**
 * @extends Factory<AnalyticAccount>
 */
class AnalyticAccountFactory extends Factory
{
    protected $model = \Modules\Accounting\Models\AnalyticAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->company.' '.$this->faker->word,
            'reference' => $this->faker->optional()->bothify('AA-####'),
            'currency_id' => function () {
                return Currency::factory()->createSafely()->id;
            },
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
