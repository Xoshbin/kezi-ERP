<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\Tax;
use App\Enums\Accounting\TaxType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run(): void
    {
        DB::transaction(function () {
            $company = Company::where('name', 'Jmeryar Solutions')->first();
            if (!$company) {
                throw new \Exception("Company 'Jmeryar Solutions' not found. Please run CompanySeeder.");
            }

            $vatPayableAccount = Account::where('code', '220101')->where('company_id', $company->id)->first();
            if (!$vatPayableAccount) {
                throw new \Exception("Account 'VAT Payable' (220101) not found. Please run AccountSeeder.");
            }

            $taxes = [
                [
                    'name' => ['en' => 'VAT 10%', 'ckb' => 'باجی بەھای زیادکراو ١٠٪', 'ar' => 'ضريبة القيمة المضافة 10%'],
                    'label_on_invoices' => ['en' => 'VAT (10%)', 'ckb' => 'باجی بەھای زیادکراو (١٠٪)', 'ar' => 'ضريبة القيمة المضافة (10%)'],
                    'rate' => 0.10,
                    'type' => TaxType::Both,
                    'tax_account_id' => $vatPayableAccount->id,
                ],
                [
                    'name' => ['en' => 'Tax Exempt', 'ckb' => 'ئازادکراو لە باج', 'ar' => 'معفي من الضريبة'],
                    'label_on_invoices' => ['en' => 'Tax Exempt', 'ckb' => 'ئازادکراو لە باج', 'ar' => 'معفي من الضريبة'],
                    'rate' => 0.00,
                    'type' => TaxType::Both,
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
                        'tax_account_id' => $taxData['tax_account_id'],
                    ]
                );
            }
        });
    }
}
