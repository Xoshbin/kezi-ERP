<?php

namespace Database\Factories;

use App\Models\BankStatement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankStatementLine>
 */
class BankStatementLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bank_statement_id' => BankStatement::factory(),
            'date' => $this->faker->date(),
            'description' => $this->faker->sentence(),
            'amount' => 15000, // Default to the test amount
        ];
    }
}
