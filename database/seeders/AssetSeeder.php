<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Journal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run()
    {
        // Fetch the company
        $company = Company::where('name', 'Jmeryar Solutions')->first();
        if (!$company) {
            throw new \Exception("Company 'Jmeryar Solutions' not found. Please run CompanySeeder.");
        }

        // Fetch accounts
        $assetAccount = Account::where('code', '1200')->where('company_id', $company->id)->first();
        if (!$assetAccount) {
            throw new \Exception("Account with code 1200 (Fixed Assets) not found. Please run AccountSeeder.");
        }

        $depreciationAccount = Account::where('code', '5100')->where('company_id', $company->id)->first();
        if (!$depreciationAccount) {
            throw new \Exception("Account with code 5100 (Depreciation Expense) not found. Please run AccountSeeder.");
        }

        // Fetch the journal
        $journal = Journal::where('name', 'Fixed Assets')->where('company_id', $company->id)->first();
        if (!$journal) {
            throw new \Exception("Journal 'Fixed Assets' not found. Please run JournalSeeder.");
        }

        Asset::updateOrCreate(
            [
                'code' => 'AST001',
                'company_id' => $company->id,
            ],
            [
                'name' => 'Office Equipment',
                'purchase_date' => Carbon::now(),
                'purchase_value' => 5000,
                'depreciation_method' => 'straight_line',
                'useful_life' => 5, // years
                'residual_value' => 500,
                'asset_account_id' => $assetAccount->id,
                'depreciation_account_id' => $depreciationAccount->id,
                'journal_id' => $journal->id,
            ]
        );
    }
}
