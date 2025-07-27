<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Journal;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find the Iraqi Dinar (IQD) currency.
        $iqdCurrency = Currency::where('code', 'IQD')->first();
        if (!$iqdCurrency) {
            throw new \Exception('IQD currency not found. Please run the CurrencySeeder first.');
        }

        // Find the default accounts and journal created by other seeders.
        $apAccount = Account::where('code', '2100')->first();
        $arAccount = Account::where('code', '1100')->first();
        $taxAccount = Account::where('code', '1500')->first(); // Assuming 'IT Equipment' is used for tax for now
        $salesDiscountAccount = Account::where('code', '4100')->first();
        $bankAccount = Account::where('code', '1010')->first();
        $outstandingReceiptsAccount = Account::where('code', '1020')->first();

        $purchaseJournal = Journal::where('type', 'purchase')->first();
        $salesJournal = Journal::where('type', 'sale')->first();
        $depreciationJournal = Journal::where('type', 'misc')->first(); // Assuming a 'misc' type journal for depreciation

        if (!$apAccount || !$arAccount || !$taxAccount || !$salesDiscountAccount || !$bankAccount || !$outstandingReceiptsAccount || !$purchaseJournal || !$salesJournal || !$depreciationJournal) {
            throw new \Exception('One or more default accounts or journals were not found. Please ensure all seeders have run successfully.');
        }

        // Create the main company record.
        Company::updateOrCreate(
            ['name' => 'Jmeryar Solutions'],
            [
                'address' => 'Slemani, Iraq',
                'tax_id' => '123456789-IQ',
                'currency_id' => $iqdCurrency->id,
                'fiscal_country' => 'IQ',
                'default_accounts_payable_id' => $apAccount->id,
                'default_tax_receivable_id' => $taxAccount->id,
                'default_purchase_journal_id' => $purchaseJournal->id,
                'default_accounts_receivable_id' => $arAccount->id,
                'default_sales_discount_account_id' => $salesDiscountAccount->id,
                'default_tax_account_id' => $taxAccount->id,
                'default_sales_journal_id' => $salesJournal->id,
                'default_depreciation_journal_id' => $depreciationJournal->id,
                'default_bank_account_id' => $bankAccount->id,
                'default_outstanding_receipts_account_id' => $outstandingReceiptsAccount->id,
            ]
        );
    }
}
