<?php

namespace Kezi\Accounting\Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Kezi\Accounting\Models\AccountGroup;

class AccountGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::where('name', 'Kezi Solutions')->first();

        if (! $company) {
            return;
        }

        $groups = [
            // Level 0 - Root Groups (GAAP 5 Types)
            [
                'code_prefix_start' => '1',
                'code_prefix_end' => '199999',
                'name' => ['en' => 'Assets', 'ar' => 'الأصول', 'ckb' => 'سامانەکان'],
                'level' => 0,
            ],
            [
                'code_prefix_start' => '2',
                'code_prefix_end' => '299999',
                'name' => ['en' => 'Liabilities', 'ar' => 'الالتزامات', 'ckb' => 'قەرزەکان'],
                'level' => 0,
            ],
            [
                'code_prefix_start' => '3',
                'code_prefix_end' => '399999',
                'name' => ['en' => 'Equity', 'ar' => 'حقوق الملكية', 'ckb' => 'سەرمایە'],
                'level' => 0,
            ],
            [
                'code_prefix_start' => '4',
                'code_prefix_end' => '499999',
                'name' => ['en' => 'Income', 'ar' => 'الإيرادات', 'ckb' => 'داهات'],
                'level' => 0,
            ],
            [
                'code_prefix_start' => '5',
                'code_prefix_end' => '599999',
                'name' => ['en' => 'Expenses', 'ar' => 'المصروفات', 'ckb' => 'خەرجییەکان'],
                'level' => 0,
            ],
            [
                'code_prefix_start' => '6',
                'code_prefix_end' => '699999',
                'name' => ['en' => 'Other Income', 'ar' => 'إيرادات أخرى', 'ckb' => 'داهاتی تر'],
                'level' => 0,
            ],

            // Level 1 - Asset Sub-Groups
            [
                'code_prefix_start' => '11',
                'code_prefix_end' => '119999',
                'name' => ['en' => 'Current Assets', 'ar' => 'الأصول المتداولة', 'ckb' => 'سامانە ڕەوانەکان'],
                'level' => 1,
            ],
            [
                'code_prefix_start' => '12',
                'code_prefix_end' => '129999',
                'name' => ['en' => 'Receivables', 'ar' => 'المدينون', 'ckb' => 'قەرزداران'],
                'level' => 1,
            ],
            [
                'code_prefix_start' => '13',
                'code_prefix_end' => '139999',
                'name' => ['en' => 'Inventory', 'ar' => 'المخزون', 'ckb' => 'کۆگا'],
                'level' => 1,
            ],
            [
                'code_prefix_start' => '15',
                'code_prefix_end' => '159999',
                'name' => ['en' => 'Fixed Assets', 'ar' => 'الأصول الثابتة', 'ckb' => 'سامانە جێگیرەکان'],
                'level' => 1,
            ],
            [
                'code_prefix_start' => '16',
                'code_prefix_end' => '169999',
                'name' => ['en' => 'Property & Buildings', 'ar' => 'العقارات والمباني', 'ckb' => 'موڵک و بیناکان'],
                'level' => 1,
            ],

            // Level 1 - Liability Sub-Groups
            [
                'code_prefix_start' => '21',
                'code_prefix_end' => '219999',
                'name' => ['en' => 'Current Liabilities', 'ar' => 'الالتزامات المتداولة', 'ckb' => 'قەرزی کورتخایەن'],
                'level' => 1,
            ],
            [
                'code_prefix_start' => '22',
                'code_prefix_end' => '229999',
                'name' => ['en' => 'Accruals & Provisions', 'ar' => 'المستحقات والمخصصات', 'ckb' => 'کەڵەکەبوو و پێشبینی'],
                'level' => 1,
            ],
            [
                'code_prefix_start' => '25',
                'code_prefix_end' => '259999',
                'name' => ['en' => 'Non-Current Liabilities', 'ar' => 'الالتزامات طويلة الأجل', 'ckb' => 'قەرزی درێژخایەن'],
                'level' => 1,
            ],

            // Level 1 - Equity Sub-Groups
            [
                'code_prefix_start' => '31',
                'code_prefix_end' => '319999',
                'name' => ['en' => 'Share Capital', 'ar' => 'رأس المال', 'ckb' => 'سەرمایەی پشکەکان'],
                'level' => 1,
            ],
            [
                'code_prefix_start' => '32',
                'code_prefix_end' => '329999',
                'name' => ['en' => 'Owner\'s Equity', 'ar' => 'حقوق الملاك', 'ckb' => 'سەرمایەی خاوەن'],
                'level' => 1,
            ],
            [
                'code_prefix_start' => '33',
                'code_prefix_end' => '339999',
                'name' => ['en' => 'Retained Earnings', 'ar' => 'الأرباح المحتجزة', 'ckb' => 'قازانجی هەڵگیراو'],
                'level' => 1,
            ],
            [
                'code_prefix_start' => '39',
                'code_prefix_end' => '399999',
                'name' => ['en' => 'Current Year Earnings', 'ar' => 'أرباح السنة الحالية', 'ckb' => 'قازانجی ساڵی ئێستا'],
                'level' => 1,
            ],

            // Level 1 - Income Sub-Groups
            [
                'code_prefix_start' => '41',
                'code_prefix_end' => '419999',
                'name' => ['en' => 'Product Sales', 'ar' => 'مبيعات المنتجات', 'ckb' => 'فرۆشتنی بەرھەم'],
                'level' => 1,
            ],
            [
                'code_prefix_start' => '42',
                'code_prefix_end' => '429999',
                'name' => ['en' => 'Service Revenue', 'ar' => 'إيراد الخدمات', 'ckb' => 'داهاتی خزمەتگوزاری'],
                'level' => 1,
            ],
            [
                'code_prefix_start' => '43',
                'code_prefix_end' => '439999',
                'name' => ['en' => 'Consulting Revenue', 'ar' => 'إيراد الاستشارات', 'ckb' => 'داهاتی ڕاوێژکاری'],
                'level' => 1,
            ],
            [
                'code_prefix_start' => '49',
                'code_prefix_end' => '499999',
                'name' => ['en' => 'Sales Discounts & Returns', 'ar' => 'خصومات ومردودات المبيعات', 'ckb' => 'داشکاندن و گەڕاندنەوە'],
                'level' => 1,
            ],

            // Level 1 - Expense Sub-Groups
            [
                'code_prefix_start' => '50',
                'code_prefix_end' => '509999',
                'name' => ['en' => 'Cost of Revenue', 'ar' => 'تكلفة الإيرادات', 'ckb' => 'تێچووی داهات'],
                'level' => 1,
            ],
            [
                'code_prefix_start' => '51',
                'code_prefix_end' => '519999',
                'name' => ['en' => 'Cost of Goods Sold', 'ar' => 'تكلفة البضاعة المباعة', 'ckb' => 'تێچووی کاڵا فرۆشراوەکان'],
                'level' => 1,
            ],
            [
                'code_prefix_start' => '53',
                'code_prefix_end' => '539999',
                'name' => ['en' => 'Operating Expenses', 'ar' => 'مصروفات التشغيل', 'ckb' => 'خەرجی کارگێڕی'],
                'level' => 1,
            ],
            [
                'code_prefix_start' => '55',
                'code_prefix_end' => '559999',
                'name' => ['en' => 'Finance Expenses', 'ar' => 'المصروفات المالية', 'ckb' => 'خەرجی دارایی'],
                'level' => 1,
            ],

            // Level 1 - Other Income Sub-Groups
            [
                'code_prefix_start' => '61',
                'code_prefix_end' => '619999',
                'name' => ['en' => 'Miscellaneous Income', 'ar' => 'إيرادات متنوعة', 'ckb' => 'داهاتی جۆراوجۆر'],
                'level' => 1,
            ],
            [
                'code_prefix_start' => '62',
                'code_prefix_end' => '629999',
                'name' => ['en' => 'Interest Income', 'ar' => 'إيراد الفوائد', 'ckb' => 'داهاتی سوو'],
                'level' => 1,
            ],
        ];

        foreach ($groups as $group) {
            AccountGroup::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'code_prefix_start' => $group['code_prefix_start'],
                ],
                [
                    'code_prefix_end' => $group['code_prefix_end'],
                    'name' => $group['name'],
                    'level' => $group['level'],
                ]
            );
        }
    }
}
