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

        // Fetch Accounts
        $bankAccountIqd = Account::where('code', '110102')->firstOrFail();
        $bankAccountUsd = Account::where('code', '110101')->firstOrFail();
        $cashAccountIqd = Account::where('code', '110202')->firstOrFail();
        $cashAccountUsd = Account::where('code', '110201')->firstOrFail();
        // You would also fetch other accounts for sales, purchases, etc. here
        $accountsReceivable = Account::where('name', 'Accounts Receivable')->firstOrFail();
        $productSales = Account::where('name', 'Product Sales')->firstOrFail();
        $accountsPayable = Account::where('name', 'Accounts Payable')->firstOrFail();
        $cogs = Account::where('name', 'Cost of Goods Sold (COGS)')->firstOrFail();


        $journals = [
            // == Primary Operational Journals ==
            [
                'name' => 'Sales',
                'type' => JournalType::Sale,
                'short_code' => 'INV',
                'currency_id' => null, // Can be used for any currency
                'default_debit_account_id' => $accountsReceivable->id,
                'default_credit_account_id' => $productSales->id,
            ],
            [
                'name' => 'Purchases',
                'type' => JournalType::Purchase,
                'short_code' => 'BILL',
                'currency_id' => null, // Can be used for any currency
                'default_debit_account_id' => $cogs->id,
                'default_credit_account_id' => $accountsPayable->id,
            ],

            // == Main Bank & Cash Journals ==
            [
                'name' => 'Bank (IQD)',
                'type' => JournalType::Bank,
                'short_code' => 'BNK-IQD',
                'currency_id' => $iqdCurrency->id,
                'default_debit_account_id' => $bankAccountIqd->id,
                'default_credit_account_id' => $bankAccountIqd->id,
            ],
            [
                'name' => 'Bank (USD)',
                'type' => JournalType::Bank,
                'short_code' => 'BNK-USD',
                'currency_id' => $usdCurrency->id,
                'default_debit_account_id' => $bankAccountUsd->id,
                'default_credit_account_id' => $bankAccountUsd->id,
            ],
            [
                'name' => 'Cash (IQD)',
                'type' => JournalType::Cash,
                'short_code' => 'CSH-IQD',
                'currency_id' => $iqdCurrency->id,
                'default_debit_account_id' => $cashAccountIqd->id,
                'default_credit_account_id' => $cashAccountIqd->id,
            ],
            [
                'name' => 'Cash (USD)',
                'type' => JournalType::Cash,
                'short_code' => 'CSH-USD',
                'currency_id' => $usdCurrency->id,
                'default_debit_account_id' => $cashAccountUsd->id,
                'default_credit_account_id' => $cashAccountUsd->id,
            ],

            // == Example: Additional Future Bank Journals ==
            [
                'name' => 'Cihan Bank',
                'type' => JournalType::Bank,
                'short_code' => 'CIHAN',
                'currency_id' => $iqdCurrency->id,
                'default_debit_account_id' => $bankAccountIqd->id, // You might create a specific account for this later
                'default_credit_account_id' => $bankAccountIqd->id,
            ],
            [
                'name' => 'NBI Bank',
                'type' => JournalType::Bank,
                'short_code' => 'NBI',
                'currency_id' => $iqdCurrency->id,
                'default_debit_account_id' => $bankAccountIqd->id, // You might create a specific account for this later
                'default_credit_account_id' => $bankAccountIqd->id,
            ],

            // == Miscellaneous Journal ==
            [
                'name' => 'Miscellaneous',
                'type' => JournalType::Miscellaneous,
                'short_code' => 'MISC',
                'currency_id' => null,
                'default_debit_account_id' => null,
                'default_credit_account_id' => null,
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
