<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JournalEntry>
 */
class JournalEntryFactory extends Factory
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
            'journal_id' => Journal::factory()->create()->id,
            'currency_id' => Currency::factory(),
            'entry_date' => $this->faker->date(),
            'reference' => $this->faker->unique()->bothify('REF-####'),
            'description' => $this->faker->sentence(),
            'source_type' => $this->faker->randomElement(['invoice', 'payment', 'expense']),
            'source_id' => $this->faker->numberBetween(1, 100),
            'created_by_user_id' => User::factory()->create()->id,
            'total_debit' => 100,
            'total_credit' => 100,
        ];
    }
}
