<?php

namespace Modules\Payment\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\Journal;
use Modules\Payment\Models\Chequebook;

class ChequebookFactory extends Factory
{
    protected $model = Chequebook::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'journal_id' => Journal::factory(),
            'name' => $this->faker->words(3, true),
            'prefix' => $this->faker->randomLetter(),
            'start_number' => 1001,
            'end_number' => 2000,
            'next_number' => 1001,
            'is_active' => true,
        ];
    }
}
