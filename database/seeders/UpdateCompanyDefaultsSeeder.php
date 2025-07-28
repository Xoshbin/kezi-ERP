<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use Illuminate\Database\Seeder;

class UpdateCompanyDefaultsSeeder extends Seeder
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

        // Find the default accounts and journal created by other seeders.
        $apAccount = Account::where('code', '210101')->where('company_id', $company->id)->first();
        $arAccount = Account::where('code', '120101')->where('company_id', $company->id)->first();
        $taxAccount = Account::where('code', '150301')->where('company_id', $company->id)->first(); // Using 'IT Equipment' for tax
        $salesDiscountAccount = Account::where('code', '600102')->where('company_id', $company->id)->first();
        $bankAccount = Account::where('code', '110101')->where('company_id', $company->id)->first(); // Using 'Bank Account (USD)'
        $outstandingReceiptsAccount = Account::where('code', '120101')->where('company_id', $company->id)->first(); // Re-using AR for now

        $purchaseJournal = Journal::where('type', 'purchase')->where('company_id', $company->id)->first();
        $salesJournal = Journal::where('type', 'sale')->where('company_id', $company->id)->first();
        $depreciationJournal = Journal::where('type', 'Miscellaneous')->where('company_id', $company->id)->first();

        if (!$apAccount || !$arAccount || !$taxAccount || !$salesDiscountAccount || !$bankAccount || !$outstandingReceiptsAccount || !$purchaseJournal || !$salesJournal || !$depreciationJournal) {
            // Providing a more detailed error message to help debug which seeder might be failing.
            $missing = [];
            if (!$apAccount) $missing[] = 'Accounts Payable Account (210101)';
            if (!$arAccount) $missing[] = 'Accounts Receivable Account (120101)';
            if (!$taxAccount) $missing[] = 'Tax Account (150301)';
            if (!$salesDiscountAccount) $missing[] = 'Sales Discount Account (600102)';
            if (!$bankAccount) $missing[] = 'Bank Account (110101)';
            if (!$outstandingReceiptsAccount) $missing[] = 'Outstanding Receipts Account (120101)';
            if (!$purchaseJournal) $missing[] = 'Purchase Journal';
            if (!$salesJournal) $missing[] = 'Sales Journal';
            if (!$depreciationJournal) $missing[] = 'Depreciation Journal';
            
            throw new \Exception('Failed to find the following required records: ' . implode(', ', $missing) . '. Please ensure all seeders have run successfully before this one.');
        }

        // Update the company with the default IDs.
        $company->update([
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
        ]);
    }
}