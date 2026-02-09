<?php

namespace App\Services\Onboarding;

use App\Models\Company;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Enums\Partners\PartnerType;
use Kezi\Foundation\Models\Partner;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Models\StockLocation;

class CompanySeederService
{
    public function seedMinimumRequired(Company $company): void
    {
        // 1. Create Default Stock Locations
        $defaultStockLocation = StockLocation::firstOrCreate(
            ['company_id' => $company->id, 'type' => StockLocationType::Internal, 'name' => 'Warehouse'],
            ['is_active' => true]
        );

        $defaultVendorLocation = StockLocation::firstOrCreate(
            ['company_id' => $company->id, 'type' => StockLocationType::Internal, 'name' => 'Vendors'],
            ['is_active' => false]
        );

        $company->update([
            'default_stock_location_id' => $defaultStockLocation->id,
            'default_vendor_location_id' => $defaultVendorLocation->id,
        ]);

        // 2. Create Base Journals
        $this->seedDefaultJournals($company);
    }

    public function seedByIndustryTemplate(Company $company, string $industryType): void
    {
        // We provide the full ERP system with a comprehensive Chart of Accounts
        // regardless of industry selection to ensure all features are available.

        // Seed Account Groups first so accounts can be assigned to them
        $groupSeeder = new \Kezi\Accounting\Database\Seeders\AccountGroupSeeder;
        $groupSeeder->run($company);

        $seeder = new \Kezi\Accounting\Database\Seeders\AccountSeeder;
        $seeder->run($company);

        // After seeding accounts, we can still set some defaults if needed,
        // though the seeder might have its own logic for 'Kezi Solutions'.
        // Let's ensure the company defaults are refreshed or set.
        $this->refreshCompanyDefaults($company);
    }

    public function seedSampleData(Company $company): void
    {
        // Create a sample customer
        Partner::create([
            'company_id' => $company->id,
            'name' => 'Sample Customer',
            'type' => PartnerType::Customer,
            'is_active' => true,
        ]);

        // Create a sample vendor
        Partner::create([
            'company_id' => $company->id,
            'name' => 'Sample Vendor',
            'type' => PartnerType::Vendor,
            'is_active' => true,
        ]);
    }

    public function markOnboardingComplete(Company $company): void
    {
        $company->update([
            'onboarding_completed_at' => now(),
        ]);
    }

    protected function seedDefaultJournals(Company $company): void
    {
        $journals = [
            ['name' => 'Bank', 'type' => 'bank', 'short_code' => 'BNK'],
            ['name' => 'Cash', 'type' => 'cash', 'short_code' => 'CSH'],
            ['name' => 'Sales', 'type' => 'sale', 'short_code' => 'INV'],
            ['name' => 'Purchases', 'type' => 'purchase', 'short_code' => 'BILL'],
            ['name' => 'Miscellaneous', 'type' => 'miscellaneous', 'short_code' => 'MISC'],
        ];

        foreach ($journals as $j) {
            $journal = Journal::firstOrCreate(
                ['company_id' => $company->id, 'short_code' => $j['short_code']],
                [
                    'name' => ['en' => $j['name']],
                    'type' => $j['type'],
                ]
            );

            // Assign default journals to company
            if ($j['type'] === 'bank') {
                $company->update(['default_bank_journal_id' => $journal->id]);
            }
            if ($j['type'] === 'sale') {
                $company->update(['default_sales_journal_id' => $journal->id]);
            }
            if ($j['type'] === 'purchase') {
                $company->update(['default_purchase_journal_id' => $journal->id]);
            }
        }
    }

    protected function refreshCompanyDefaults(Company $company): void
    {
        // Map common account codes from AccountSeeder to company defaults
        $mappings = [
            '120101' => 'default_accounts_receivable_id',
            '210101' => 'default_accounts_payable_id',
            '110101' => 'default_bank_account_id',
            '130101' => 'default_inventory_account_id',
            '510101' => 'default_expense_account_id', // COGS
            '410101' => 'default_income_account_id',   // Product Sales
        ];

        foreach ($mappings as $code => $field) {
            $account = Account::where('company_id', $company->id)->where('code', $code)->first();
            if ($account) {
                $company->update([$field => $account->id]);
            }
        }
    }
}
