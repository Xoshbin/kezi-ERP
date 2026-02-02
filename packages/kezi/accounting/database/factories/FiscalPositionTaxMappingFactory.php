<?php

namespace Kezi\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Accounting\Models\FiscalPositionTaxMapping;

/**
 * @extends Factory<FiscalPositionTaxMapping>
 */
class FiscalPositionTaxMappingFactory extends Factory
{
    protected $model = FiscalPositionTaxMapping::class;

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
            'original_tax_id' => \Kezi\Accounting\Models\Tax::factory(),
            'mapped_tax_id' => \Kezi\Accounting\Models\Tax::factory(),
        ];
    }
}
