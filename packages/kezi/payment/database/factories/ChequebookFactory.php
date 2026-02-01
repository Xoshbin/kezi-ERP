<?php

namespace Kezi\Payment\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Accounting\Models\Journal;
use Kezi\Payment\Models\Chequebook;

class ChequebookFactory extends Factory
{
    protected $model = Chequebook::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'journal_id' => Journal::factory(),
            'name' => $this->faker->words(3, true),
            'bank_name' => $this->faker->company().' Bank',
            'bank_account_number' => $this->faker->bankAccountNumber(),
            'prefix' => $this->faker->randomLetter(),
            'digits' => 6,
            'start_number' => 1001,
            'end_number' => 2000,
            'next_number' => 1001,
            'is_active' => true,
        ];
    }
}
