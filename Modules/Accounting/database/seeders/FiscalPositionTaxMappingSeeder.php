<?php

namespace Modules\Accounting\Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Seeder;
use RuntimeException;

class FiscalPositionTaxMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch the domestic fiscal position
        $domesticPosition = \Modules\Accounting\Models\FiscalPosition::where('name->en', 'Domestic (Iraq)')->first();

        if (! $domesticPosition) {
            throw new RuntimeException('Fiscal position "Domestic (Iraq)" not found. Please run the FiscalPositionSeeder first.');
        }

        // Fetch the VAT 10% tax
        $vat10 = Tax::where('name->en', 'VAT 10%')->first();

        if (! $vat10) {
            throw new RuntimeException('Tax "VAT 10%" not found. Please run the TaxSeeder first.');
        }

        // Create the tax mapping
        \Modules\Accounting\Models\FiscalPositionTaxMapping::updateOrCreate(
            [
                'fiscal_position_id' => $domesticPosition->id,
                'original_tax_id' => $vat10->id,
            ],
            [
                'mapped_tax_id' => $vat10->id,
            ]
        );
    }
}
