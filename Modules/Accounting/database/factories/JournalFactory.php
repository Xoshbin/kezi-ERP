<?php

namespace Modules\Accounting\Database\Factories;

use App\Models\Company;
use Modules\Accounting\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Enums\Accounting\JournalType;

/**
 * @extends Factory<Journal>
 */
class JournalFactory extends Factory
{
    protected $model = \Modules\Accounting\Models\Journal::class;

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
            'default_debit_account_id' => Account::factory(),
            'default_credit_account_id' => Account::factory(),
        ];
    }
}
