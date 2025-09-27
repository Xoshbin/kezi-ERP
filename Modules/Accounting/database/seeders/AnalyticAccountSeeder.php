<?php

namespace Database\Seeders;

use App\Models\AnalyticAccount;
use App\Models\AnalyticPlan;
use App\Models\Company;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Seeder;

class AnalyticAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            $company = Company::where('name', 'Jmeryar Solutions')->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new Exception("Company 'Jmeryar Solutions' not found. Please run the CompanySeeder first.");
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
