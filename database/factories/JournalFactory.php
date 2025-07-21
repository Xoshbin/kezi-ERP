<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Journal>
 */
class JournalFactory extends Factory
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
            'name' => $this->faker->company,
            'type' => $this->faker->randomElement(['general', 'cash', 'bank']),
            'short_code' => strtoupper($this->faker->lexify('???')),
            'currency_id' => Currency::factory()->create()->id,
        ];
    }
}
