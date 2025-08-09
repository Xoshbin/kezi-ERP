<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Currency::updateOrCreate(
            ['code' => 'USD'],
            [
                'name' => ['en' => 'US Dollar', 'ckb' => 'دۆلاری ئەمریکی'],
                'symbol' => '$',
                'exchange_rate' => 1.0,
                'is_active' => true,
            ]
        );

        Currency::updateOrCreate(
            ['code' => 'IQD'],
            [
                'name' => ['en' => 'Iraqi Dinar', 'ckb' => 'دیناری عێراقی'],
                'symbol' => 'ع.د',
                'exchange_rate' => 1460.0,
                'is_active' => true,
            ]
        );
    }
}
