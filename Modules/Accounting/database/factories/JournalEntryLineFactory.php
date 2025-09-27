<?php

namespace Modules\Accounting\Database\Factories;

use App\Models\Account;
use App\Models\JournalEntry;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalEntryLineFactory extends Factory
{
    public function definition(): array
    {
        $isDebit = $this->faker->boolean;
        $amount = Money::of($this->faker->randomFloat(2, 100, 10000), 'USD');

        return [
            'journal_entry_id' => JournalEntry::factory(),
            'company_id' => function (array $attributes) {
                return JournalEntry::find($attributes['journal_entry_id'])->company_id;
            },
            'account_id' => \Modules\Accounting\Models\Account::factory(),
            'partner_id' => null,
            'description' => $this->faker->sentence(),
            'debit' => $isDebit ? $amount : 0,
            'credit' => ! $isDebit ? $amount : 0,
        ];
    }
}
