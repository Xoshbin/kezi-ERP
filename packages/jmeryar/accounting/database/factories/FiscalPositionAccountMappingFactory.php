<?php

namespace Jmeryar\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\Accounting\Models\FiscalPositionAccountMapping;

/**
 * @extends Factory<FiscalPositionAccountMapping>
 */
class FiscalPositionAccountMappingFactory extends Factory
{
    protected $model = \Jmeryar\Accounting\Models\FiscalPositionAccountMapping::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'fiscal_position_id' => \Jmeryar\Accounting\Models\FiscalPosition::factory(),
            'original_account_id' => \Jmeryar\Accounting\Models\Account::factory(),
            'mapped_account_id' => \Jmeryar\Accounting\Models\Account::factory(),
        ];
    }
}
