<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\FiscalPosition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FiscalPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $company = Company::where('name', 'Jmeryar Solutions')->first();

            if (!$company) {
                throw new \Exception('Company "Jmeryar Solutions" not found. Please run the CompanySeeder first.');
            }

            FiscalPosition::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => ['en' => 'Domestic (Iraq)', 'ckb' => 'ناوخۆیی (عێراق)'],
                ],
                [
                    'name' => ['en' => 'Domestic (Iraq)', 'ckb' => 'ناوخۆیی (عێراق)'],
                    'country' => 'IQ',
                ]
            );
        });
    }
}
