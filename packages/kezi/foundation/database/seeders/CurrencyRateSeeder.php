<?php

namespace Kezi\Foundation\Database\Seeders;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\CurrencyRate;

class CurrencyRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::where('name', 'Kezi Solutions')->first();
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
