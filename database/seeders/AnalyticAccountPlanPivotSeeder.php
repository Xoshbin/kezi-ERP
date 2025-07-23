<?php

namespace Database\Seeders;

use App\Models\AnalyticAccount;
use App\Models\AnalyticPlan;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AnalyticAccountPlanPivotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run(): void
    {
        try {
            $company = Company::where('name', 'Jmeryar Solutions')->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new \Exception("Company 'Jmeryar Solutions' not found. Please run the CompanySeeder first.");
        }

        try {
            $plan = AnalyticPlan::where('name', 'Projects')->where('company_id', $company->id)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new \Exception("Analytic Plan 'Projects' not found. Please run the AnalyticPlanSeeder first.");
        }

        try {
            $accountAlpha = AnalyticAccount::where('name', 'Project Alpha')->where('company_id', $company->id)->firstOrFail();
            $accountBeta = AnalyticAccount::where('name', 'Project Beta')->where('company_id', $company->id)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new \Exception("Analytic Accounts not found. Please run the AnalyticAccountSeeder first.");
        }

        // Attach accounts to the plan if not already attached
        if (!$plan->analyticAccounts()->where('analytic_account_id', $accountAlpha->id)->exists()) {
            $plan->analyticAccounts()->attach($accountAlpha->id);
        }

        if (!$plan->analyticAccounts()->where('analytic_account_id', $accountBeta->id)->exists()) {
            $plan->analyticAccounts()->attach($accountBeta->id);
        }
    }
}
