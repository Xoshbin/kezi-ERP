<?php

namespace Kezi\Accounting\Database\Seeders;

use App\Models\Company;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Seeder;
use Kezi\Accounting\Models\AnalyticAccount;
use Kezi\Accounting\Models\AnalyticPlan;

class AnalyticAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            $company = Company::where('name', 'Kezi Solutions')->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new Exception("Company 'Kezi Solutions' not found. Please run the CompanySeeder first.");
        }

        try {
            $plan = AnalyticPlan::where('name->en', 'Projects')->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new Exception("Analytic Plan 'Projects' not found. Please run the AnalyticPlanSeeder first.");
        }

        AnalyticAccount::updateOrCreate(
            [
                'company_id' => $company->id,
                'plan_id' => $plan->id,
                'code' => 'P001',
            ],
            [
                'name' => 'Project Alpha',
            ]
        );

        AnalyticAccount::updateOrCreate(
            [
                'company_id' => $company->id,
                'plan_id' => $plan->id,
                'code' => 'P002',
            ],
            [
                'name' => 'Project Beta',
            ]
        );
    }
}
