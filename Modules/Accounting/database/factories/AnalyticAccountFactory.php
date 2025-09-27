<?php

namespace Database\Factories;

use App\Models\AnalyticAccount;
use App\Models\Company;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticAccount>
 */
class AnalyticAccountFactory extends Factory
{
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
            'currency_id' => \Modules\Foundation\Models\Currency::factory()->createSafely()->id,
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
