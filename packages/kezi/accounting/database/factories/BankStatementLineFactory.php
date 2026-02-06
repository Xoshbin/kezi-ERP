<?php

namespace Kezi\Accounting\Database\Factories;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Accounting\Models\BankStatement;

/**
 * @extends Factory<\Kezi\Accounting\Models\BankStatementLine>
 */
class BankStatementLineFactory extends Factory
{
    protected $model = \Kezi\Accounting\Models\BankStatementLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $bankStatement = BankStatement::factory();

        return [
            'bank_statement_id' => $bankStatement,
            'company_id' => function (array $attributes) {
                return BankStatement::find($attributes['bank_statement_id'])->company_id;
            },
            'date' => $this->faker->date(),
            'description' => $this->faker->sentence(),
            'amount' => Money::of(150, 'USD'), // Default to a USD money object
        ];
    }
}
