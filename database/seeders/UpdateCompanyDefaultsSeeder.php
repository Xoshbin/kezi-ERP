<?php

namespace Database\Seeders;

use Exception;
use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use Illuminate\Database\Seeder;

class UpdateCompanyDefaultsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder links the default accounts and journals to the main company record.
     */
    public function run(): void
    {
        $company = Company::where('name', 'Jmeryar Solutions')->first();
        if (!$company) {
            throw new Exception('Company "Jmeryar Solutions" not found. Please run the CompanySeeder first.');
        }

        // Find the default accounts and journals created by other seeders.
        $apAccount = Account::where('code', '210101')->where('company_id', $company->id)->first();
        $arAccount = Account::where('code', '120101')->where('company_id', $company->id)->first();
        $salesDiscountAccount = Account::where('code', '490101')->where('company_id', $company->id)->first();
        $bankAccount = Account::where('code', '110101')->where('company_id', $company->id)->first(); // Using 'Bank Account (USD)'
        $outstandingReceiptsAccount = Account::where('code', '110301')->where('company_id', $company->id)->first();
        $taxAccount = Account::where('code', '220101')->where('company_id', $company->id)->first(); // Using 'VAT Payable' as a sensible default

        // ** FIXED: Query journals by their unique short_code for reliability **
        $purchaseJournal = Journal::where('short_code', 'BILL')->where('company_id', $company->id)->first();
        $salesJournal = Journal::where('short_code', 'INV')->where('company_id', $company->id)->first();
        $depreciationJournal = Journal::where('short_code', 'DEPR')->where('company_id', $company->id)->first();

        // Check if all required records were found before attempting to update the company.
        $missing = [];
        if (!$apAccount) $missing[] = 'Accounts Payable Account (210101)';
        if (!$arAccount) $missing[] = 'Accounts Receivable Account (120101)';
        if (!$salesDiscountAccount) $missing[] = 'Sales Discount Account (490101)';
        if (!$bankAccount) $missing[] = 'Bank Account (110101)';
        if (!$outstandingReceiptsAccount) $missing[] = 'Outstanding Receipts Account (110301)';
        if (!$taxAccount) $missing[] = 'Tax Account (220101)';
        if (!$purchaseJournal) $missing[] = 'Purchase Journal (short_code: BILL)';
        if (!$salesJournal) $missing[] = 'Sales Journal (short_code: INV)';
        if (!$depreciationJournal) $missing[] = 'Depreciation Journal (short_code: DEPR)';

        if (!empty($missing)) {
            throw new Exception('Failed to find the following required records: ' . implode(', ', $missing) . '. Please ensure all seeders have run successfully before this one.');
        }

        // Update the company with the default IDs.
        $company->update([
            'default_accounts_payable_id' => $apAccount->id,
            'default_accounts_receivable_id' => $arAccount->id,
            'default_sales_discount_account_id' => $salesDiscountAccount->id,
            'default_tax_account_id' => $taxAccount->id,
            'default_purchase_journal_id' => $purchaseJournal->id,
            'default_sales_journal_id' => $salesJournal->id,
            'default_depreciation_journal_id' => $depreciationJournal->id,
            'default_bank_account_id' => $bankAccount->id,
            'default_outstanding_receipts_account_id' => $outstandingReceiptsAccount->id,
        ]);
    }
}
