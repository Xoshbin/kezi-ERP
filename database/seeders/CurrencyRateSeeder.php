<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\CurrencyRate;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrencyRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all active currencies
        $currencies = Currency::where('is_active', true)->get();

        foreach ($currencies as $currency) {
            // Skip IQD as it's typically the base currency with rate 1.0
            if ($currency->code === 'IQD') {
                continue;
            }

            // Create initial rates for the past 30 days
            $startDate = Carbon::now()->subDays(30);

            for ($i = 0; $i <= 30; $i++) {
                $date = $startDate->copy()->addDays($i);

                // Use the current exchange_rate from the currency table as base
                // Add some variation to simulate historical rates
                // $baseRate = $currency->exchange_rate;
                // $variation = rand(-5, 5) / 100; // ±5% variation
                // $rate = $baseRate * (1 + $variation);

                CurrencyRate::updateOrCreate(
                    [
                        'currency_id' => $currency->id,
                        'effective_date' => $date->toDateString(),
                    ],
                    [
                        // 'rate' => $rate,
                        'rate' => 1460,
                        'source' => 'seeder',
                    ]
                );
            }
        }

        // Create a rate for IQD (base currency) with rate 1.0
        $iqd = Currency::where('code', 'IQD')->first();
        if ($iqd) {
            CurrencyRate::updateOrCreate(
                [
                    'currency_id' => $iqd->id,
                    'effective_date' => Carbon::today()->toDateString(),
                ],
                [
                    'rate' => 1.0,
                    'source' => 'seeder',
                ]
            );
        }
    }
}
