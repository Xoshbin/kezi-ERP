<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch the company
        $company = Company::where('name', 'Jmeryar Solutions')->first();
        if (!$company) {
            throw new \Exception("Company 'Jmeryar Solutions' not found. Please run the CompanySeeder.");
        }

        // Fetch the income account
        $incomeAccount = Account::where('code', '420101')->where('company_id', $company->id)->first();
        if (!$incomeAccount) {
            throw new \Exception("Account with code '420101' (Service Revenue) not found. Please run the AccountSeeder.");
        }

        // Fetch the expense account
        $expenseAccount = Account::where('code', '510101')->where('company_id', $company->id)->first();
        if (!$expenseAccount) {
            throw new \Exception("Account with code '510101' (Cost of Goods Sold) not found. Please run the AccountSeeder.");
        }

        Product::updateOrCreate(
            [
                'company_id' => $company->id,
                'sku' => 'CONS-001',
            ],
            [
                'name' => 'Consulting Services',
                'description' => 'Standard consulting services.',
                'unit_price' => 150000.00,
                'type' => 'Service',
                'income_account_id' => $incomeAccount->id,
                'expense_account_id' => $expenseAccount->id,
            ]
        );
    }
}
