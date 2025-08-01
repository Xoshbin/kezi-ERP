<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Account;
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
            'company_id' => Company::factory(),
            'name' => $this->faker->company,
            'type' => $this->faker->randomElement(['general', 'cash', 'bank']),
            'short_code' => $this->faker->unique()->bothify('???'),
            'currency_id' => function (array $attributes) {
                return Company::find($attributes['company_id'])->currency_id;
            },
            'default_debit_account_id' => Account::factory(),
            'default_credit_account_id' => Account::factory(),
        ];
    }
}
