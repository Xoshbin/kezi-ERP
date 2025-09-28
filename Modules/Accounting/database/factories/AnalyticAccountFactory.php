<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\AnalyticAccount;
use App\Models\Company;
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
            'company_id' => Company::factory()->create()->id,
            'name' => $this->faker->company . ' ' . $this->faker->word,
            'reference' => $this->faker->optional()->bothify('AA-####'),
            'currency_id' => Currency::factory()->createSafely()->id,
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
