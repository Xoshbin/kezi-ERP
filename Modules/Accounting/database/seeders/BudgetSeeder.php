<?php

namespace Modules\Accounting\Database\Seeders;

use App\Models\Account;
use App\Models\AnalyticPlan;
use App\Models\Budget;
use App\Models\Company;
use Exception;
use Illuminate\Database\Seeder;

class BudgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch the company
        $company = Company::where('name', 'Jmeryar Solutions')->first();
        if (! $company) {
            throw new Exception("Company 'Jmeryar Solutions' not found. Please run CompanySeeder.");
        }

        // Fetch accounts
        $salesAccount = \Modules\Accounting\Models\Account::where('code', '4000')->first();
        if (! $salesAccount) {
            throw new Exception("Account with code '4000' (Sales) not found. Please run AccountSeeder.");
        }

        $marketingAccount = \Modules\Accounting\Models\Account::where('code', '4100')->first();
        if (! $marketingAccount) {
            throw new Exception("Account with code '4100' (Marketing) not found. Please run AccountSeeder.");
        }

        $adminAccount = \Modules\Accounting\Models\Account::where('code', '4200')->first();
        if (! $adminAccount) {
            throw new Exception("Account with code '4200' (Administration) not found. Please run AccountSeeder.");
        }

        // Fetch analytic plan
        $analyticPlan = \Modules\Accounting\Models\AnalyticPlan::where('name->en', 'Department')->first();
        if (! $analyticPlan) {
            throw new Exception("Analytic Plan 'Department' not found. Please run AnalyticPlanSeeder.");
        }

        \Modules\Accounting\Models\Budget::updateOrCreate(
            [
                'name' => '2025 Annual Budget',
                'company_id' => $company->id,
            ],
            [
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'status' => 'draft',
                'notes' => 'Initial budget plan for fiscal year 2025',
            ]
        );
    }
}
