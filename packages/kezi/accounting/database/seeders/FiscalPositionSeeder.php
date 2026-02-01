<?php

namespace Kezi\Accounting\Database\Seeders;

use App\Models\Company;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Models\FiscalPosition;

class FiscalPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $company = Company::where('name', 'Kezi Solutions')->first();

            if (! $company) {
                throw new Exception('Company "Kezi Solutions" not found. Please run the CompanySeeder first.');
            }

            FiscalPosition::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => ['en' => 'Domestic (Iraq)', 'ckb' => 'ناوخۆیی (عێراق)', 'ar' => 'محلي (العراق)'],
                ],
                [
                    'name' => ['en' => 'Domestic (Iraq)', 'ckb' => 'ناوخۆیی (عێراق)', 'ar' => 'محلي (العراق)'],
                    'country' => 'IQ',
                ]
            );
        });
    }
}
