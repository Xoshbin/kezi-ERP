<?php

namespace Jmeryar\Accounting\Database\Factories;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\JournalEntry;

class JournalEntryLineFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Jmeryar\Accounting\Models\JournalEntryLine>
     */
    protected $model = \Jmeryar\Accounting\Models\JournalEntryLine::class;

    public function definition(): array
    {
        $isDebit = $this->faker->boolean;
        $amount = Money::of($this->faker->randomFloat(2, 100, 10000), 'USD');

        return [
            'journal_entry_id' => JournalEntry::factory(),
            'company_id' => function (array $attributes) {
                return JournalEntry::find($attributes['journal_entry_id'])->company_id;
            },
            'account_id' => Account::factory(),
            'partner_id' => null,
            'description' => $this->faker->sentence(),
            'debit' => $isDebit ? $amount : 0,
            'credit' => ! $isDebit ? $amount : 0,
        ];
    }
}
