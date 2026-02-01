<?php

namespace Kezi\Accounting\Database\Factories;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Accounting\Models\BankStatement;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Currency;

/**
 * @extends Factory<BankStatement>
 */
class BankStatementFactory extends Factory
{
    protected $model = \Kezi\Accounting\Models\BankStatement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'journal_id' => Journal::factory(),
            'currency_id' => Currency::firstOrCreate(['code' => 'IQD'], ['name' => 'Iraqi Dinar'])->id,
            'reference' => $this->faker->unique()->bothify('REF-####-????'),
            'date' => $this->faker->date(),
            'starting_balance' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
            'ending_balance' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
        ];
    }
}
