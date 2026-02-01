<?php

namespace Jmeryar\Accounting\Database\Factories;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\Accounting\Models\Journal;
use Jmeryar\Accounting\Models\JournalEntry;

/**
 * @extends Factory<JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    protected $model = \Jmeryar\Accounting\Models\JournalEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'journal_id' => function (array $attributes) {
                return Journal::factory()->create([
                    'company_id' => $attributes['company_id'] ?? Company::factory(),
                ])->id;
            },
            'currency_id' => function (array $attributes) {
                return Company::find($attributes['company_id'])->currency_id;
            },
            'entry_date' => $this->faker->date(),
            'reference' => $this->faker->unique()->bothify('REF-####'),
            'description' => $this->faker->sentence(),
            'source_type' => $this->faker->randomElement(['invoice', 'payment', 'expense']),
            'source_id' => $this->faker->numberBetween(1, 100),
            'created_by_user_id' => User::factory(),
            'total_debit' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
            'total_credit' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
        ];
    }
}
