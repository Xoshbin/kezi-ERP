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
        \Modules\Foundation\Models\Currency::updateOrCreate(
            ['code' => 'USD'],
            [
                'name' => ['en' => 'US Dollar', 'ckb' => 'دۆلاری ئەمریکی', 'ar' => 'دولار أمريكي'],
                'symbol' => '$',
                'is_active' => true,
                'decimal_places' => 2,
            ]
        );

        \Modules\Foundation\Models\Currency::updateOrCreate(
            ['code' => 'IQD'],
            [
                'name' => ['en' => 'Iraqi Dinar', 'ckb' => 'دیناری عێراقی', 'ar' => 'دينار عراقي'],
                'symbol' => 'ع.د',
                'is_active' => true,
                'decimal_places' => 3,
            ]
        );
    }
}
