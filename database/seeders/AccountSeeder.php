<?php
// FILE: database/seeders/AccountSeeder.php

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
            // === ASSETS ===
            // Bank & Cash
            ['code' => '110101', 'name' => ['en' => 'Bank Account (USD)', 'ckb' => 'حسابی بانک (دۆلار)'], 'type' => AccountType::BankAndCash],
            ['code' => '110102', 'name' => ['en' => 'Bank Account (IQD)', 'ckb' => 'حسابی بانک (دینار)'], 'type' => AccountType::BankAndCash],
            ['code' => '110201', 'name' => ['en' => 'Cash (USD)', 'ckb' => 'پارەی نەخت (دۆلار)'], 'type' => AccountType::BankAndCash],
            ['code' => '110202', 'name' => ['en' => 'Cash (IQD)', 'ckb' => 'پارەی نەخت (دینار)'], 'type' => AccountType::BankAndCash],

            // Current Assets
            ['code' => '110301', 'name' => ['en' => 'Outstanding Receipts', 'ckb' => 'وەرگرتنی نەتمام'], 'type' => AccountType::CurrentAssets],
            ['code' => '110401', 'name' => ['en' => 'Bank Suspense Account', 'ckb' => 'حسابی گومانی بانک'], 'type' => AccountType::CurrentAssets],
            ['code' => '120101', 'name' => ['en' => 'Accounts Receivable', 'ckb' => 'حسابە وەرگیراوەکان'], 'type' => AccountType::Receivable],
            ['code' => '120102', 'name' => ['en' => 'VAT Receivable', 'ckb' => 'باجی بەھای زیادکراو وەرگیراو'], 'type' => AccountType::CurrentAssets],
            ['code' => '120201', 'name' => ['en' => 'Prepaid Expenses', 'ckb' => 'خەرجی پێشەوەداپردراو'], 'type' => AccountType::Prepayments],
            ['code' => '120301', 'name' => ['en' => 'Employee Advances', 'ckb' => 'پێشەکی فەرمانبەران'], 'type' => AccountType::CurrentAssets],
            ['code' => '130101', 'name' => ['en' => 'Inventory', 'ckb' => 'کۆگا'], 'type' => AccountType::CurrentAssets],

            // Fixed Assets
            ['code' => '150101', 'name' => ['en' => 'Office Equipment', 'ckb' => 'ئامێری نووسینگە'], 'type' => AccountType::FixedAssets, 'can_create_assets' => true],
            ['code' => '150199', 'name' => ['en' => 'Acc. Depreciation - Office Equipment', 'ckb' => 'بەھاکەمبوونی کەڵەکەبوو - ئامێری نووسینگە'], 'type' => AccountType::FixedAssets, 'can_create_assets' => false],
            ['code' => '150201', 'name' => ['en' => 'Vehicles', 'ckb' => 'ئۆتۆمبێلەکان'], 'type' => AccountType::FixedAssets, 'can_create_assets' => true],
            ['code' => '150299', 'name' => ['en' => 'Acc. Depreciation - Vehicles', 'ckb' => 'بەھاکەمبوونی کەڵەکەبوو - ئۆتۆمبێلەکان'], 'type' => AccountType::FixedAssets, 'can_create_assets' => false],
            ['code' => '150301', 'name' => ['en' => 'IT Equipment', 'ckb' => 'ئامێری تەکنەلۆژیا'], 'type' => AccountType::FixedAssets, 'can_create_assets' => true],
            ['code' => '160101', 'name' => ['en' => 'Land', 'ckb' => 'زەوی'], 'type' => AccountType::FixedAssets, 'can_create_assets' => true],
            ['code' => '160201', 'name' => ['en' => 'Buildings', 'ckb' => 'بیناکان'], 'type' => AccountType::FixedAssets, 'can_create_assets' => true],
            ['code' => '160299', 'name' => ['en' => 'Acc. Depreciation - Buildings', 'ckb' => 'بەھاکەمبوونی کەڵەکەبوو - بیناکان'], 'type' => AccountType::FixedAssets, 'can_create_assets' => false],

            // === LIABILITIES ===
            // Current Liabilities
            ['code' => '210101', 'name' => ['en' => 'Accounts Payable', 'ckb' => 'حسابە دراوەکان'], 'type' => AccountType::Payable],
            ['code' => '210201', 'name' => ['en' => 'Stock Interim (Received)', 'ckb' => 'کەڵەکەبووی کاتیی (وەرگیراو)'], 'type' => AccountType::CurrentLiabilities],
            ['code' => '220101', 'name' => ['en' => 'VAT Payable', 'ckb' => 'باجی بەھای زیادکراو'], 'type' => AccountType::CurrentLiabilities],
            ['code' => '220201', 'name' => ['en' => 'Unearned Revenue', 'ckb' => 'داھاتی نەبردی'], 'type' => AccountType::CurrentLiabilities],
            ['code' => '220301', 'name' => ['en' => 'Outstanding Payments', 'ckb' => 'پارەدانە نەتمامەکان'], 'type' => AccountType::CurrentLiabilities],
            ['code' => '220501', 'name' => ['en' => 'Accrued Expenses', 'ckb' => 'خەرجییە کەڵەکەبووەکان'], 'type' => AccountType::CurrentLiabilities],

            // Long-Term Liabilities
            ['code' => '250101', 'name' => ['en' => 'Long-Term Debt', 'ckb' => 'قەرزی درێژخایەن'], 'type' => AccountType::NonCurrentLiabilities],

            // === EQUITY ===
            ['code' => '310101', 'name' => ['en' => 'Share Capital', 'ckb' => 'سەرمایەی پشک'], 'type' => AccountType::Equity],
            ['code' => '320101', 'name' => ['en' => 'Owner\'s Equity', 'ckb' => 'سەرمایەی خاوەن'], 'type' => AccountType::Equity],
            ['code' => '330101', 'name' => ['en' => 'Retained Earnings', 'ckb' => 'قازانجی راگیراو'], 'type' => AccountType::Equity],
            ['code' => '390101', 'name' => ['en' => 'Current Year Earnings', 'ckb' => 'قازانجی ساڵی ئێستا'], 'type' => AccountType::CurrentYearEarnings],

            // === INCOME ===
            ['code' => '410101', 'name' => ['en' => 'Product Sales', 'ckb' => 'فرۆشتنی بەرھەم'], 'type' => AccountType::Income],
            ['code' => '420101', 'name' => ['en' => 'Service Revenue', 'ckb' => 'داھاتی خزمەتگوزاری'], 'type' => AccountType::Income],
            ['code' => '430101', 'name' => ['en' => 'Consulting Revenue', 'ckb' => 'داھاتی ڕاوێژکاری'], 'type' => AccountType::Income],
            ['code' => '490101', 'name' => ['en' => 'Sales Discounts & Returns', 'ckb' => 'داشکاندن و گەڕاندنەوەی فرۆشتن'], 'type' => AccountType::Income], // Contra-Revenue
            ['code' => '610101', 'name' => ['en' => 'Miscellaneous Income', 'ckb' => 'داھاتی جۆراوجۆر'], 'type' => AccountType::OtherIncome],
            ['code' => '610201', 'name' => ['en' => 'Inventory Price Difference (Income)', 'ckb' => 'جیاوازی نرخ - داھات'], 'type' => AccountType::OtherIncome],
            ['code' => '610301', 'name' => ['en' => 'Cash Difference Gain', 'ckb' => 'قازانجی جیاوازی پارە'], 'type' => AccountType::OtherIncome],
            ['code' => '620101', 'name' => ['en' => 'Interest Income', 'ckb' => 'داهاتی سوو'], 'type' => AccountType::OtherIncome],


            // === EXPENSES ===
            ['code' => '510101', 'name' => ['en' => 'Cost of Goods Sold (COGS)', 'ckb' => 'تێچووی کاڵای فرۆشراو'], 'type' => AccountType::CostOfRevenue],
            ['code' => '510201', 'name' => ['en' => 'Inventory Adjustment Expense', 'ckb' => 'خەرجی گۆڕینی کەڵەکەبوو'], 'type' => AccountType::Expense],
            ['code' => '510301', 'name' => ['en' => 'Inventory Price Difference (Expense)', 'ckb' => 'جیاوازی نرخ - خەرجی'], 'type' => AccountType::Expense],
            ['code' => '510401', 'name' => ['en' => 'Cash Difference Loss', 'ckb' => 'زەرەری جیاوازی پارە'], 'type' => AccountType::Expense],
            ['code' => '530101', 'name' => ['en' => 'Salaries and Wages', 'ckb' => 'مووچە و کرێ'], 'type' => AccountType::Expense],
            ['code' => '530201', 'name' => ['en' => 'Rent Expense', 'ckb' => 'خەرجی کرێ'], 'type' => AccountType::Expense],
            ['code' => '530301', 'name' => ['en' => 'Depreciation Expense', 'ckb' => 'خەرجی بەھاکەمبوون'], 'type' => AccountType::Depreciation],
            ['code' => '530401', 'name' => ['en' => 'Bank Charges Expense', 'ckb' => 'خەرجی بانک'], 'type' => AccountType::Expense],
            ['code' => '530501', 'name' => ['en' => 'Utilities Expense', 'ckb' => 'خەرجی خزمەتگوزارییە گشتییەکان'], 'type' => AccountType::Expense],
            ['code' => '550101', 'name' => ['en' => 'Interest Expense', 'ckb' => 'خەرجی سوو'], 'type' => AccountType::Expense],
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
