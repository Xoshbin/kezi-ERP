<?php

namespace Kezi\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Accounting\Models\FiscalPositionAccountMapping;

/**
 * @extends Factory<FiscalPositionAccountMapping>
 */
class FiscalPositionAccountMappingFactory extends Factory
{
    protected $model = \Kezi\Accounting\Models\FiscalPositionAccountMapping::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'fiscal_position_id' => \Kezi\Accounting\Models\FiscalPosition::factory(),
            'original_account_id' => \Kezi\Accounting\Models\Account::factory(),
            'mapped_account_id' => \Kezi\Accounting\Models\Account::factory(),
        ];
    }
}
