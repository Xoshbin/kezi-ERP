<?php

namespace Modules\Accounting\Database\Seeders;

use Exception;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Modules\Accounting\Models\AnalyticPlan;

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
