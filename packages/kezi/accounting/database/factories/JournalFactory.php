<?php

namespace Kezi\Accounting\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Accounting\Enums\Accounting\JournalType;
use Kezi\Accounting\Models\Account;

/**
 * @extends Factory<Journal>
 */
class JournalFactory extends Factory
{
    protected $model = \Kezi\Accounting\Models\Journal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->words(2, true),
            'type' => JournalType::Miscellaneous,
            'short_code' => $this->faker->unique()->bothify('???'),
            'currency_id' => function (array $attributes) {
                return Company::find($attributes['company_id'])->currency_id;
            },
            'default_debit_account_id' => function (array $attributes) {
                return Account::factory()->create(['company_id' => $attributes['company_id']])->id;
            },
            'default_credit_account_id' => function (array $attributes) {
                return Account::factory()->create(['company_id' => $attributes['company_id']])->id;
            },
        ];
    }
}
