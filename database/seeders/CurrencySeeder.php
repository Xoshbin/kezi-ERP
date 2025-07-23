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
                'name' => 'US Dollar',
                'symbol' => '$',
                'exchange_rate' => 1.0,
                'is_active' => true,
            ]
        );

        Currency::updateOrCreate(
            ['code' => 'IQD'],
            [
                'name' => 'Iraqi Dinar',
                'symbol' => 'IQD',
                'exchange_rate' => 1460.0,
                'is_active' => true,
            ]
        );
    }
}
