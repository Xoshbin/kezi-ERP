<?php

namespace Jmeryar\Accounting\Database\Seeders;

use App\Models\Company;
use Exception;
use Illuminate\Database\Seeder;
use Jmeryar\Accounting\Models\AnalyticPlan;

class AnalyticPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @throws Exception
     */
    public function run(): void
    {
        $company = Company::where('name', 'Jmeryar Solutions')->first();

        if (! $company) {
            throw new Exception('Company "Jmeryar Solutions" not found. Please run the CompanySeeder first.');
        }

        AnalyticPlan::updateOrCreate(
            [
                'company_id' => $company->id,
                'name' => ['en' => 'Projects', 'ckb' => 'پڕۆژەکان', 'ar' => 'مشاريع'],
            ],
            [
                'name' => ['en' => 'Projects', 'ckb' => 'پڕۆژەکان', 'ar' => 'مشاريع'],
            ]
        );
    }
}
