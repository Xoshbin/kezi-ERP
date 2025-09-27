<?php

namespace Modules\Accounting\Database\Factories;

use App\Enums\Accounting\JournalType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Journal>
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
            'type' => JournalType::Miscellaneous,
            'short_code' => $this->faker->unique()->bothify('???'),
            'currency_id' => function (array $attributes) {
                return Company::find($attributes['company_id'])->currency_id;
            },
            'default_debit_account_id' => \Modules\Accounting\Models\Account::factory(),
            'default_credit_account_id' => \Modules\Accounting\Models\Account::factory(),
        ];
    }
}
