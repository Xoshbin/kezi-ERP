<?php

namespace Database\Seeders;

use App\Enums\Accounting\JournalType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Journal;
use Illuminate\Database\Seeder;

class JournalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::firstOrFail();

        // Fetch Currencies
        $iqdCurrency = Currency::where('code', 'IQD')->firstOrFail();
        $usdCurrency = Currency::where('code', 'USD')->firstOrFail();

        // Fetch Accounts using their unique codes for reliability
        $bankAccountIqd = Account::where('code', '110102')->firstOrFail();
        $bankAccountUsd = Account::where('code', '110101')->firstOrFail();
        $cashAccountIqd = Account::where('code', '110202')->firstOrFail();
        $cashAccountUsd = Account::where('code', '110201')->firstOrFail();
        $accountsReceivable = Account::where('code', '120101')->firstOrFail();
        $productSales = Account::where('code', '410101')->firstOrFail();
        $accountsPayable = Account::where('code', '210101')->firstOrFail();
        $cogs = Account::where('code', '510101')->firstOrFail();
        $depreciationExpenseAccount = Account::where('code', '530301')->firstOrFail();

        $journals = [
            // == Primary Operational Journals ==
            [
                'name' => ['en' => 'Sales', 'ckb' => 'فرۆشتن', 'ar' => 'مبيعات'],
                'type' => JournalType::Sale,
                'short_code' => 'INV',
                'currency_id' => null,
                'default_debit_account_id' => $accountsReceivable->id,
                'default_credit_account_id' => $productSales->id,
            ],
            [
                'name' => ['en' => 'Purchases', 'ckb' => 'کڕین', 'ar' => 'مشتريات'],
                'type' => JournalType::Purchase,
                'short_code' => 'BILL',
                'currency_id' => null,
                'default_debit_account_id' => $cogs->id,
                'default_credit_account_id' => $accountsPayable->id,
            ],

            // == Main Bank & Cash Journals ==
            [
                'name' => ['en' => 'Bank (IQD)', 'ckb' => 'بانک (دینار)', 'ar' => 'بنك (دينار عراقي)'],
                'type' => JournalType::Bank,
                'short_code' => 'BNK-IQD',
                'currency_id' => $iqdCurrency->id,
                'default_debit_account_id' => $bankAccountIqd->id,
                'default_credit_account_id' => $bankAccountIqd->id,
            ],
            [
                'name' => ['en' => 'Bank (USD)', 'ckb' => 'بانک (دۆلار)', 'ar' => 'بنك (دولار أمريكي)'],
                'type' => JournalType::Bank,
                'short_code' => 'BNK-USD',
                'currency_id' => $usdCurrency->id,
                'default_debit_account_id' => $bankAccountUsd->id,
                'default_credit_account_id' => $bankAccountUsd->id,
            ],
            [
                'name' => ['en' => 'Cash (IQD)', 'ckb' => 'پارەی نەخت (دینار)', 'ar' => 'نقد (دينار عراقي)'],
                'type' => JournalType::Cash,
                'short_code' => 'CSH-IQD',
                'currency_id' => $iqdCurrency->id,
                'default_debit_account_id' => $cashAccountIqd->id,
                'default_credit_account_id' => $cashAccountIqd->id,
            ],
            [
                'name' => ['en' => 'Cash (USD)', 'ckb' => 'پارەی نەخت (دۆلار)', 'ar' => 'نقد (دولار أمريكي)'],
                'type' => JournalType::Cash,
                'short_code' => 'CSH-USD',
                'currency_id' => $usdCurrency->id,
                'default_debit_account_id' => $cashAccountUsd->id,
                'default_credit_account_id' => $cashAccountUsd->id,
            ],

            // == Miscellaneous Journals ==
            [
                'name' => ['en' => 'Miscellaneous Operations', 'ckb' => 'کردارە جۆراوجۆرەکان', 'ar' => 'عمليات متنوعة'],
                'type' => JournalType::Miscellaneous,
                'short_code' => 'MISC',
                'currency_id' => null,
                'default_debit_account_id' => null,
                'default_credit_account_id' => null,
            ],
            // ** NEW: Specific journal for depreciation entries **
            [
                'name' => ['en' => 'Depreciation', 'ckb' => 'داشکان', 'ar' => 'إهلاك'],
                'type' => JournalType::Miscellaneous,
                'short_code' => 'DEPR',
                'currency_id' => null,
                'default_debit_account_id' => $depreciationExpenseAccount->id,
                'default_credit_account_id' => null, // Credit account will vary per asset type
            ],

            // == Opening Balance Journal ==
            [
                'name' => [
                    'en' => 'Opening Balance',
                    'ckb' => 'کردنەوەی سەرمایە',
                    'ar' => 'الرصيد الافتتاحي',
                ],
                'type' => JournalType::Miscellaneous,
                'short_code' => 'OPEN',
                'currency_id' => null,
                'default_debit_account_id' => null, // Set in opening entry
                'default_credit_account_id' => null, // Set in opening entry
            ],
        ];

        foreach ($journals as $journalData) {
            Journal::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'short_code' => $journalData['short_code'],
                ],
                $journalData
            );
        }
    }
}
