<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Seeder;
use App\Enums\Accounting\AccountType;

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
            ['code' => '110101', 'name' => ['en' => 'Bank Account (USD)', 'ckb' => 'حسابی بانک (دۆلار)'], 'type' => AccountType::Asset],
            ['code' => '110102', 'name' => ['en' => 'Bank Account (IQD)', 'ckb' => 'حسابی بانک (دینار)'], 'type' => AccountType::Asset],
            ['code' => '110201', 'name' => ['en' => 'Cash (USD)', 'ckb' => 'پارەی نەخت (دۆلار)'], 'type' => AccountType::Asset],
            ['code' => '110202', 'name' => ['en' => 'Cash (IQD)', 'ckb' => 'پارەی نەخت (دینار)'], 'type' => AccountType::Asset],
            ['code' => '120101', 'name' => ['en' => 'Accounts Receivable', 'ckb' => 'حسابە وەرگیراوەکان'], 'type' => AccountType::Asset],
            ['code' => '150101', 'name' => ['en' => 'Office Equipment', 'ckb' => 'ئامێری نووسینگە'], 'type' => AccountType::Asset],
            ['code' => '150199', 'name' => ['en' => 'Acc. Depreciation - Office Equipment', 'ckb' => 'بەھاکەمبوونی کەڵەکەبوو - ئامێری نووسینگە'], 'type' => AccountType::Asset],
            ['code' => '150201', 'name' => ['en' => 'Vehicles', 'ckb' => 'ئۆتۆمبێلەکان'], 'type' => AccountType::Asset],
            ['code' => '150299', 'name' => ['en' => 'Acc. Depreciation - Vehicles', 'ckb' => 'بەھاکەمبوونی کەڵەکەبوو - ئۆتۆمبێلەکان'], 'type' => AccountType::Asset],
            ['code' => '150301', 'name' => ['en' => 'IT Equipment', 'ckb' => 'ئامێری تەکنەلۆژیا'], 'type' => AccountType::Asset],

            // Liabilities
            ['code' => '210101', 'name' => ['en' => 'Accounts Payable', 'ckb' => 'حسابە دراوەکان'], 'type' => AccountType::Liability],
            ['code' => '220101', 'name' => ['en' => 'VAT Payable', 'ckb' => 'باجی بەھای زیادکراو'], 'type' => AccountType::Liability],

            // Equity
            ['code' => '310101', 'name' => ['en' => 'Share Capital', 'ckb' => 'سەرمایەی پشک'], 'type' => AccountType::Equity],
            ['code' => '330101', 'name' => ['en' => 'Retained Earnings', 'ckb' => 'قازانجی راگیراو'], 'type' => AccountType::Equity],
            ['code' => '390101', 'name' => ['en' => 'Current Year Earnings', 'ckb' => 'قازانجی ساڵی ئێستا'], 'type' => AccountType::Equity],
            ['code' => '320101', 'name' => ['en' => 'Owner\'s Equity', 'ckb' => 'سەرمایەی خاوەن'], 'type' => AccountType::Equity],

            // Income
            ['code' => '410101', 'name' => ['en' => 'Product Sales', 'ckb' => 'فرۆشتنی بەرھەم'], 'type' => AccountType::Income],
            ['code' => '420101', 'name' => ['en' => 'Service Revenue', 'ckb' => 'داھاتی خزمەتگوزاری'], 'type' => AccountType::Income],

            ['code' => '600101', 'name' => ['en' => 'Consulting Revenue', 'ckb' => 'داھاتی ڕاوێژکاری'], 'type' => AccountType::Income],
            ['code' => '600102', 'name' => ['en' => 'Sales Discounts & Returns', 'ckb' => 'داشکاندن و گەڕاندنەوەی فرۆشتن'], 'type' => AccountType::Income],

            // Expenses
            ['code' => '510101', 'name' => ['en' => 'Cost of Goods Sold (COGS)', 'ckb' => 'تێچووی کاڵای فرۆشراو'], 'type' => AccountType::Expense],
            ['code' => '530101', 'name' => ['en' => 'Salaries and Wages', 'ckb' => 'مووچە و کرێ'], 'type' => AccountType::Expense],
            ['code' => '530201', 'name' => ['en' => 'Rent Expense', 'ckb' => 'خەرجی کرێ'], 'type' => AccountType::Expense],
            ['code' => '530301', 'name' => ['en' => 'Depreciation Expense', 'ckb' => 'خەرجی بەھاکەمبوون'], 'type' => AccountType::Expense],
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
