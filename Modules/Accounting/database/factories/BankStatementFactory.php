<?php

namespace Modules\Accounting\Database\Factories;

use App\Models\BankStatement;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Journal;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankStatement>
 */
class BankStatementFactory extends Factory
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
            'journal_id' => Journal::factory(),
            'currency_id' => \Modules\Foundation\Models\Currency::firstOrCreate(['code' => 'IQD'], ['name' => 'Iraqi Dinar'])->id,
            'reference' => $this->faker->unique()->bothify('REF-####-????'),
            'date' => $this->faker->date(),
            'starting_balance' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
            'ending_balance' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
        ];
    }
}
