<?php

namespace Database\Seeders;

use App\Models\Company;
use Exception;
use Illuminate\Database\Seeder;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Models\StockLocation;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find the Iraqi Dinar (IQD) currency.
        $iqdCurrency = \Kezi\Foundation\Models\Currency::where('code', 'IQD')->first();
        if (! $iqdCurrency) {
            throw new Exception('IQD currency not found. Please run the CurrencySeeder first.');
        }

        // Create the main company record.
        $company = Company::updateOrCreate(
            ['name' => 'Kezi Solutions'],
            [
                'address' => 'Slemani, Iraq',
                'tax_id' => '123456789-IQ',
                'currency_id' => $iqdCurrency->id,
                'fiscal_country' => 'IQ',
            ]
        );

        // Create the default locations required by the company.
        $defaultStockLocation = StockLocation::updateOrCreate(
            ['company_id' => $company->id, 'type' => StockLocationType::Internal, 'name' => 'Warehouse'],
            ['is_active' => true]
        );

        $defaultVendorLocation = StockLocation::updateOrCreate(
            ['company_id' => $company->id, 'type' => StockLocationType::Internal, 'name' => 'Vendors'],
            ['is_active' => false]
        );

        // Now, update the company with the IDs of the newly created locations.
        $company->update([
            'default_stock_location_id' => $defaultStockLocation->id,
            'default_vendor_location_id' => $defaultVendorLocation->id,
        ]);
    }
}
