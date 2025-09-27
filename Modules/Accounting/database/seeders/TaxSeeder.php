<?php

namespace Database\Seeders;

use App\Enums\Accounting\TaxType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Tax;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @throws Exception
     */
    public function run(): void
    {
        DB::transaction(function () {
            $company = Company::where('name', 'Jmeryar Solutions')->first();
            if (! $company) {
                throw new Exception("Company 'Jmeryar Solutions' not found. Please run CompanySeeder.");
            }

            $vatPayableAccount = \Modules\Accounting\Models\Account::where('code', '220101')->where('company_id', $company->id)->first();
            if (! $vatPayableAccount) {
                throw new Exception("Account 'VAT Payable' (220101) not found. Please run AccountSeeder.");
            }

            $taxes = [
                [
                    'name' => ['en' => 'VAT 10%', 'ckb' => 'باجی بەھای زیادکراو ١٠٪', 'ar' => 'ضريبة القيمة المضافة 10%'],
                    'label_on_invoices' => ['en' => 'VAT (10%)', 'ckb' => 'باجی بەھای زیادکراو (١٠٪)', 'ar' => 'ضريبة القيمة المضافة (10%)'],
                    'rate' => 10,
                    'type' => TaxType::Both,
                    'is_recoverable' => true,
                    'tax_account_id' => $vatPayableAccount->id,
                ],
                [
                    'name' => ['en' => 'VAT 5% Non-Recoverable', 'ckb' => 'باجی بەھای زیادکراو ٥٪ ناگەڕێتەوە', 'ar' => 'ضريبة القيمة المضافة 5% غير قابلة للاسترداد'],
                    'label_on_invoices' => ['en' => 'VAT (5%)', 'ckb' => 'باجی بەھای زیادکراو (٥٪)', 'ar' => 'ضريبة القيمة المضافة (5%)'],
                    'rate' => 5,
                    'type' => TaxType::Purchase,
                    'is_recoverable' => false,
                    'tax_account_id' => $vatPayableAccount->id,
                ],
                [
                    'name' => ['en' => 'Tax Exempt', 'ckb' => 'ئازادکراو لە باج', 'ar' => 'معفي من الضريبة'],
                    'label_on_invoices' => ['en' => 'Tax Exempt', 'ckb' => 'ئازادکراو لە باج', 'ar' => 'معفي من الضريبة'],
                    'rate' => 0.00,
                    'type' => TaxType::Both,
                    'is_recoverable' => false,
                    'tax_account_id' => $vatPayableAccount->id,
                ],
            ];

            foreach ($taxes as $taxData) {
                Tax::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'name' => $taxData['name'],
                    ],
                    [
                        'name' => $taxData['name'],
                        'label_on_invoices' => $taxData['label_on_invoices'],
                        'rate' => $taxData['rate'],
                        'type' => $taxData['type'],
                        'is_recoverable' => $taxData['is_recoverable'],
                        'tax_account_id' => $taxData['tax_account_id'],
                    ]
                );
            }
        });
    }
}
