<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\FiscalPositionTaxMapping;

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
            'fiscal_position_id' => \Modules\Accounting\Models\FiscalPosition::factory(),
            'original_tax_id' => \Modules\Accounting\Models\Tax::factory(),
            'mapped_tax_id' => \Modules\Accounting\Models\Tax::factory(),
        ];
    }
}
