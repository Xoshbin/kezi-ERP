<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Currency;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find the Iraqi Dinar (IQD) currency.
        $iqdCurrency = Currency::where('code', 'IQD')->first();

        // If the currency is not found, throw an exception to enforce seeder order.
        if (!$iqdCurrency) {
            throw new \Exception('IQD currency not found. Please run the CurrencySeeder first.');
        }

        // Create the main company record.
        Company::updateOrCreate(
            ['name' => 'Jmeryar Solutions'],
            [
                'address' => 'Slemani, Iraq',
                'tax_id' => '123456789-IQ',
                'currency_id' => $iqdCurrency->id,
                'fiscal_country' => 'IQ',
            ]
        );
    }
}
