<?php

namespace Database\Factories;

use Brick\Money\Money;
use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalEntryLineFactory extends Factory
{
    public function definition(): array
    {
        $isDebit = $this->faker->boolean;
        $amount = Money::of($this->faker->randomFloat(2, 100, 10000), 'USD');

        return [
            'journal_entry_id' => JournalEntry::factory(),
            'account_id' => Account::factory(),
            'partner_id' => null,
            'description' => $this->faker->sentence(),
            'debit' => $isDebit ? $amount : 0,
            'credit' => !$isDebit ? $amount : 0,
        ];
    }
}
