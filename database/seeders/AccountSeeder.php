<?php

namespace Database\Seeders;

use Exception;
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
            throw new Exception('Company "Jmeryar Solutions" not found. Please run the CompanySeeder first.');
        }

        $accounts = [
            // === ASSETS ===
            // Bank & Cash
            ['code' => '110101', 'name' => ['en' => 'Bank Account (USD)', 'ckb' => 'حسابی بانک (دۆلار)', 'ar' => 'حساب بنكي (دولار أمريكي)'], 'type' => AccountType::BankAndCash],
            ['code' => '110102', 'name' => ['en' => 'Bank Account (IQD)', 'ckb' => 'حسابی بانک (دینار)', 'ar' => 'حساب بنكي (دينار عراقي)'], 'type' => AccountType::BankAndCash],
            ['code' => '110201', 'name' => ['en' => 'Cash (USD)', 'ckb' => 'پارەی نەخت (دۆلار)', 'ar' => 'نقد (دولار أمريكي)'], 'type' => AccountType::BankAndCash],
            ['code' => '110202', 'name' => ['en' => 'Cash (IQD)', 'ckb' => 'پارەی نەخت (دینار)', 'ar' => 'نقد (دينار عراقي)'], 'type' => AccountType::BankAndCash],

            // Current Assets
            ['code' => '110301', 'name' => ['en' => 'Outstanding Receipts', 'ckb' => 'وەرگرتنی نەتمام', 'ar' => 'إيصالات معلقة'], 'type' => AccountType::CurrentAssets],
            ['code' => '110401', 'name' => ['en' => 'Bank Suspense Account', 'ckb' => 'حسابی گومانی بانک', 'ar' => 'حساب بنكي معلق'], 'type' => AccountType::CurrentAssets],
            ['code' => '120101', 'name' => ['en' => 'Accounts Receivable', 'ckb' => 'حسابە وەرگیراوەکان', 'ar' => 'حسابات مدينة'], 'type' => AccountType::Receivable],
            ['code' => '120102', 'name' => ['en' => 'VAT Receivable', 'ckb' => 'باجی بەھای زیادکراو وەرگیراو', 'ar' => 'ضريبة القيمة المضافة مستحقة القبض'], 'type' => AccountType::CurrentAssets],
            ['code' => '120201', 'name' => ['en' => 'Prepaid Expenses', 'ckb' => 'خەرجی پێشەوەداپردراو', 'ar' => 'مصروفات مدفوعة مقدماً'], 'type' => AccountType::Prepayments],
            ['code' => '120301', 'name' => ['en' => 'Employee Advances', 'ckb' => 'پێشەکی فەرمانبەران', 'ar' => 'سلف الموظفين'], 'type' => AccountType::CurrentAssets],
            ['code' => '130101', 'name' => ['en' => 'Inventory', 'ckb' => 'کۆگا', 'ar' => 'مخزون'], 'type' => AccountType::CurrentAssets],

            // Fixed Assets
            ['code' => '150101', 'name' => ['en' => 'Office Equipment', 'ckb' => 'ئامێری نووسینگە', 'ar' => 'معدات مكتبية'], 'type' => AccountType::FixedAssets, 'can_create_assets' => true],
            ['code' => '150199', 'name' => ['en' => 'Acc. Depreciation - Office Equipment', 'ckb' => 'بەھاکەمبوونی کەڵەکەبوو - ئامێری نووسینگە', 'ar' => 'إهلاك متراكم - معدات مكتبية'], 'type' => AccountType::FixedAssets, 'can_create_assets' => false],
            ['code' => '150201', 'name' => ['en' => 'Vehicles', 'ckb' => 'ئۆتۆمبێلەکان', 'ar' => 'مركبات'], 'type' => AccountType::FixedAssets, 'can_create_assets' => true],
            ['code' => '150299', 'name' => ['en' => 'Acc. Depreciation - Vehicles', 'ckb' => 'بەھاکەمبوونی کەڵەکەبوو - ئۆتۆمبێلەکان', 'ar' => 'إهلاك متراكم - مركبات'], 'type' => AccountType::FixedAssets, 'can_create_assets' => false],
            ['code' => '150301', 'name' => ['en' => 'IT Equipment', 'ckb' => 'ئامێری تەکنەلۆژیا', 'ar' => 'معدات تقنية المعلومات'], 'type' => AccountType::FixedAssets, 'can_create_assets' => true],
            ['code' => '160101', 'name' => ['en' => 'Land', 'ckb' => 'زەوی', 'ar' => 'أراضي'], 'type' => AccountType::FixedAssets, 'can_create_assets' => true],
            ['code' => '160201', 'name' => ['en' => 'Buildings', 'ckb' => 'بیناکان', 'ar' => 'مباني'], 'type' => AccountType::FixedAssets, 'can_create_assets' => true],
            ['code' => '160299', 'name' => ['en' => 'Acc. Depreciation - Buildings', 'ckb' => 'بەھاکەمبوونی کەڵەکەبوو - بیناکان', 'ar' => 'إهلاك متراكم - مباني'], 'type' => AccountType::FixedAssets, 'can_create_assets' => false],

            // === LIABILITIES ===
            // Current Liabilities
            ['code' => '210101', 'name' => ['en' => 'Accounts Payable', 'ckb' => 'حسابە دراوەکان', 'ar' => 'حسابات دائنة'], 'type' => AccountType::Payable],
            ['code' => '210201', 'name' => ['en' => 'Stock Interim (Received)', 'ckb' => 'کەڵەکەبووی کاتیی (وەرگیراو)', 'ar' => 'مخزون مؤقت (مستلم)'], 'type' => AccountType::CurrentLiabilities],
            ['code' => '220101', 'name' => ['en' => 'VAT Payable', 'ckb' => 'باجی بەھای زیادکراو', 'ar' => 'ضريبة القيمة المضافة مستحقة الدفع'], 'type' => AccountType::CurrentLiabilities],
            ['code' => '220201', 'name' => ['en' => 'Unearned Revenue', 'ckb' => 'داھاتی نەبردی', 'ar' => 'إيراد غير مكتسب'], 'type' => AccountType::CurrentLiabilities],
            ['code' => '220301', 'name' => ['en' => 'Outstanding Payments', 'ckb' => 'پارەدانە نەتمامەکان', 'ar' => 'مدفوعات معلقة'], 'type' => AccountType::CurrentLiabilities],
            ['code' => '220501', 'name' => ['en' => 'Accrued Expenses', 'ckb' => 'خەرجییە کەڵەکەبووەکان', 'ar' => 'مصروفات مستحقة'], 'type' => AccountType::CurrentLiabilities],

            // Long-Term Liabilities
            ['code' => '250101', 'name' => ['en' => 'Long-Term Debt', 'ckb' => 'قەرزی درێژخایەن', 'ar' => 'ديون طويلة الأجل'], 'type' => AccountType::NonCurrentLiabilities],

            // === EQUITY ===
            ['code' => '310101', 'name' => ['en' => 'Share Capital', 'ckb' => 'سەرمایەی پشک', 'ar' => 'رأس المال'], 'type' => AccountType::Equity],
            ['code' => '320101', 'name' => ['en' => 'Owner\'s Equity', 'ckb' => 'سەرمایەی خاوەن', 'ar' => 'حقوق الملكية'], 'type' => AccountType::Equity],
            ['code' => '330101', 'name' => ['en' => 'Retained Earnings', 'ckb' => 'قازانجی راگیراو', 'ar' => 'أرباح محتجزة'], 'type' => AccountType::Equity],
            ['code' => '390101', 'name' => ['en' => 'Current Year Earnings', 'ckb' => 'قازانجی ساڵی ئێستا', 'ar' => 'أرباح السنة الحالية'], 'type' => AccountType::CurrentYearEarnings],

            // === INCOME ===
            ['code' => '410101', 'name' => ['en' => 'Product Sales', 'ckb' => 'فرۆشتنی بەرھەم', 'ar' => 'مبيعات المنتجات'], 'type' => AccountType::Income],
            ['code' => '420101', 'name' => ['en' => 'Service Revenue', 'ckb' => 'داھاتی خزمەتگوزاری', 'ar' => 'إيراد الخدمات'], 'type' => AccountType::Income],
            ['code' => '430101', 'name' => ['en' => 'Consulting Revenue', 'ckb' => 'داھاتی ڕاوێژکاری', 'ar' => 'إيراد الاستشارات'], 'type' => AccountType::Income],
            ['code' => '490101', 'name' => ['en' => 'Sales Discounts & Returns', 'ckb' => 'داشکاندن و گەڕاندنەوەی فرۆشتن', 'ar' => 'خصومات ومردودات المبيعات'], 'type' => AccountType::Income], // Contra-Revenue
            ['code' => '610101', 'name' => ['en' => 'Miscellaneous Income', 'ckb' => 'داھاتی جۆراوجۆر', 'ar' => 'إيرادات متنوعة'], 'type' => AccountType::OtherIncome],
            ['code' => '610201', 'name' => ['en' => 'Inventory Price Difference (Income)', 'ckb' => 'جیاوازی نرخ - داھات', 'ar' => 'فرق سعر المخزون (إيراد)'], 'type' => AccountType::OtherIncome],
            ['code' => '610301', 'name' => ['en' => 'Cash Difference Gain', 'ckb' => 'قازانجی جیاوازی پارە', 'ar' => 'ربح فرق النقد'], 'type' => AccountType::OtherIncome],
            ['code' => '620101', 'name' => ['en' => 'Interest Income', 'ckb' => 'داهاتی سوو', 'ar' => 'إيراد الفوائد'], 'type' => AccountType::OtherIncome],


            // === EXPENSES ===
            ['code' => '510101', 'name' => ['en' => 'Cost of Goods Sold (COGS)', 'ckb' => 'تێچووی کاڵای فرۆشراو', 'ar' => 'تكلفة البضاعة المباعة'], 'type' => AccountType::CostOfRevenue],
            ['code' => '510201', 'name' => ['en' => 'Inventory Adjustment Expense', 'ckb' => 'خەرجی گۆڕینی کەڵەکەبوو', 'ar' => 'مصروف تسوية المخزون'], 'type' => AccountType::Expense],
            ['code' => '510301', 'name' => ['en' => 'Inventory Price Difference (Expense)', 'ckb' => 'جیاوازی نرخ - خەرجی', 'ar' => 'فرق سعر المخزون (مصروف)'], 'type' => AccountType::Expense],
            ['code' => '510401', 'name' => ['en' => 'Cash Difference Loss', 'ckb' => 'زەرەری جیاوازی پارە', 'ar' => 'خسارة فرق النقد'], 'type' => AccountType::Expense],
            ['code' => '530101', 'name' => ['en' => 'Salaries and Wages', 'ckb' => 'مووچە و کرێ', 'ar' => 'رواتب وأجور'], 'type' => AccountType::Expense],
            ['code' => '530201', 'name' => ['en' => 'Rent Expense', 'ckb' => 'خەرجی کرێ', 'ar' => 'مصروف الإيجار'], 'type' => AccountType::Expense],
            ['code' => '530301', 'name' => ['en' => 'Depreciation Expense', 'ckb' => 'خەرجی بەھاکەمبوون', 'ar' => 'مصروف الإهلاك'], 'type' => AccountType::Depreciation],
            ['code' => '530401', 'name' => ['en' => 'Bank Charges Expense', 'ckb' => 'خەرجی بانک', 'ar' => 'مصروف رسوم البنك'], 'type' => AccountType::Expense],
            ['code' => '530501', 'name' => ['en' => 'Utilities Expense', 'ckb' => 'خەرجی خزمەتگوزارییە گشتییەکان', 'ar' => 'مصروف المرافق العامة'], 'type' => AccountType::Expense],
            ['code' => '550101', 'name' => ['en' => 'Interest Expense', 'ckb' => 'خەرجی سوو', 'ar' => 'مصروف الفوائد'], 'type' => AccountType::Expense],
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
                    'can_create_assets' => $accountData['can_create_assets'] ?? false,
                ]
            );
        }
    }
}
