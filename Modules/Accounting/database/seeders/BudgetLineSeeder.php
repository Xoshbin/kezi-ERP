<?php

namespace Modules\Accounting\Database\Seeders;

use Exception;
use Illuminate\Database\Seeder;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\AnalyticAccount;
use Modules\Accounting\Models\Budget;
use Modules\Accounting\Models\BudgetLine;
use App\Models\Company;

class BudgetLineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     *
     * @throws Exception
     */
    public function run()
    {
        // Fetch the company
        $company = Company::where('name', 'Jmeryar Solutions')->first();
        if (! $company) {
            throw new Exception('Company "Jmeryar Solutions" not found. Please run CompanySeeder.');
        }

        // Fetch the budget
        $budget = Budget::where('name', '2025 Annual Budget')->where('company_id', $company->id)->first();
        if (! $budget) {
            throw new Exception('Budget "2025 Annual Budget" not found. Please run BudgetSeeder.');
        }

        // Fetch accounts
        $salesAccount = Account::where('code', '4000')->where('company_id', $company->id)->first();
        if (! $salesAccount) {
            throw new Exception('Account with code 4000 (Sales) not found.');
        }

        $marketingAccount = Account::where('code', '4100')->where('company_id', $company->id)->first();
        if (! $marketingAccount) {
            throw new Exception('Account with code 4100 (Marketing) not found.');
        }

        $adminAccount = Account::where('code', '4200')->where('company_id', $company->id)->first();
        if (! $adminAccount) {
            throw new Exception('Account with code 4200 (Administration) not found.');
        }

        // Fetch analytic accounts
        $salesAnalytic = AnalyticAccount::where('name', 'Sales Department')->where('company_id', $company->id)->first();
        if (! $salesAnalytic) {
            throw new Exception('Analytic Account "Sales Department" not found.');
        }

        $marketingAnalytic = AnalyticAccount::where('name', 'Marketing Department')->where('company_id', $company->id)->first();
        if (! $marketingAnalytic) {
            throw new Exception('Analytic Account "Marketing Department" not found.');
        }

        $adminAnalytic = AnalyticAccount::where('name', 'Administration Department')->where('company_id', $company->id)->first();
        if (! $adminAnalytic) {
            throw new Exception('Analytic Account "Administration Department" not found.');
        }

        // Seed budget lines
        $budgetLines = [
            [
                'budget_id' => $budget->id,
                'account_id' => $salesAccount->id,
                'analytic_account_id' => $salesAnalytic->id,
                'amount' => 500000,
                'company_id' => $company->id,
                'notes' => 'Budget allocation for Sales',
            ],
            [
                'budget_id' => $budget->id,
                'account_id' => $marketingAccount->id,
                'analytic_account_id' => $marketingAnalytic->id,
                'amount' => 200000,
                'company_id' => $company->id,
                'notes' => 'Budget allocation for Marketing',
            ],
            [
                'budget_id' => $budget->id,
                'account_id' => $adminAccount->id,
                'analytic_account_id' => $adminAnalytic->id,
                'amount' => 300000,
                'company_id' => $company->id,
                'notes' => 'Budget allocation for Administration',
            ],
        ];

        foreach ($budgetLines as $line) {
            BudgetLine::updateOrCreate(
                [
                    'budget_id' => $line['budget_id'],
                    'account_id' => $line['account_id'],
                    'analytic_account_id' => $line['analytic_account_id'],
                    'company_id' => $line['company_id'],
                ],
                $line
            );
        }
    }
}
