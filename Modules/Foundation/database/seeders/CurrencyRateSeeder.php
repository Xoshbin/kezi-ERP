<?php

namespace Modules\Foundation\Database\Seeders;

use App\Models\Company;
use App\Models\Currency;
use App\Models\CurrencyRate;
use Carbon\Carbon;
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
        $usd = \Modules\Foundation\Models\Currency::where('code', 'USD')->first();

        \Modules\Foundation\Models\CurrencyRate::updateOrCreate(
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
