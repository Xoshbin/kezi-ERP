<?php

namespace Database\Factories;

use Brick\Money\Money;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankStatement>
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
            'currency_id' => Currency::firstOrCreate(['code' => 'IQD'], ['name' => 'Iraqi Dinar'])->id,
            'reference' => $this->faker->unique()->bothify('REF-####-????'),
            'date' => $this->faker->date(),
            'starting_balance' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
            'ending_balance' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
        ];
    }
}
