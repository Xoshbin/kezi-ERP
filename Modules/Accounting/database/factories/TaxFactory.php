<?php

namespace Modules\Accounting\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Enums\Accounting\TaxType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Tax;

/**
 * @extends Factory<Tax>
 */
class TaxFactory extends Factory
{
    protected $model = \Modules\Accounting\Models\Tax::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->word,
            'rate' => $this->faker->randomFloat(2, 0, 100),
            'type' => $this->faker->randomElement([TaxType::Sales, TaxType::Purchase, TaxType::Both]),
            'is_active' => $this->faker->boolean,
            'tax_account_id' => Account::factory(),
            'computation' => 'percent',
        ];
    }
}
