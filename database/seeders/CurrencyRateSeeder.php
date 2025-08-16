<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\Currency;
use App\Models\CurrencyRate;
use Illuminate\Database\Seeder;

class CurrencyRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::where('name', 'Jmeryar Solutions')->first();
        // Get all active currencies
        $usd = Currency::where('code', 'USD')->first();

         CurrencyRate::updateOrCreate(
                [
                    'currency_id' => $usd->id,
                    'effective_date' => Carbon::today(),
                ],
                [
                    'company_id' => $company->id,
                    'rate' => 1460,
                    'source' => 'seeder',
                ]
            );
    }
}
