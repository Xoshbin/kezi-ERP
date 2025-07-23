<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::where('name', 'Jmeryar Solutions')->first();

        if (!$company) {
            throw new \Exception('Company "Jmeryar Solutions" not found. Please run the CompanySeeder first.');
        }

        $accounts = [
            // Assets
            ['code' => '110101', 'name' => 'Bank Account (USD)', 'type' => 'Asset'],
            ['code' => '110102', 'name' => 'Bank Account (IQD)', 'type' => 'Asset'],
            ['code' => '110201', 'name' => 'Cash (USD)', 'type' => 'Asset'],
            ['code' => '110202', 'name' => 'Cash (IQD)', 'type' => 'Asset'],
            ['code' => '120101', 'name' => 'Accounts Receivable', 'type' => 'Asset'],
            ['code' => '150101', 'name' => 'Office Equipment', 'type' => 'Asset'],
            ['code' => '150199', 'name' => 'Acc. Depreciation - Office Equipment', 'type' => 'Asset'],
            ['code' => '150201', 'name' => 'Vehicles', 'type' => 'Asset'],
            ['code' => '150299', 'name' => 'Acc. Depreciation - Vehicles', 'type' => 'Asset'],

            // Liabilities
            ['code' => '210101', 'name' => 'Accounts Payable', 'type' => 'Liability'],
            ['code' => '220101', 'name' => 'VAT Payable', 'type' => 'Liability'],

            // Equity
            ['code' => '310101', 'name' => 'Share Capital', 'type' => 'Equity'],
            ['code' => '330101', 'name' => 'Retained Earnings', 'type' => 'Equity'],
            ['code' => '390101', 'name' => 'Current Year Earnings', 'type' => 'Equity'],
            ['code' => '320101', 'name' => 'Owner\'s Equity', 'type' => 'Equity'],

            // Income
            ['code' => '410101', 'name' => 'Product Sales', 'type' => 'Income'],
            ['code' => '420101', 'name' => 'Service Revenue', 'type' => 'Income'],

            // Expenses
            ['code' => '510101', 'name' => 'Cost of Goods Sold (COGS)', 'type' => 'Expense'],
            ['code' => '530101', 'name' => 'Salaries and Wages', 'type' => 'Expense'],
            ['code' => '530201', 'name' => 'Rent Expense', 'type' => 'Expense'],
            ['code' => '530301', 'name' => 'Depreciation Expense', 'type' => 'Expense'],
        ];

        foreach ($accounts as $accountData) {
            Account::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $accountData['code'],
                ],
                [
                    'name' => $accountData['name'],
                    'type' => $accountData['type'],
                ]
            );
        }
    }
}
