<?php

namespace App\Services\Onboarding;

use App\Models\Company;
use Kezi\Accounting\Enums\Accounting\AccountType;
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
        switch ($industryType) {
            case 'retail':
                $this->seedRetailTemplate($company);
                break;
            case 'manufacturing':
                $this->seedManufacturingTemplate($company);
                break;
            case 'services':
                $this->seedServicesTemplate($company);
                break;
            default:
                $this->seedGenericTemplate($company);
                break;
        }
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

    protected function seedRetailTemplate(Company $company): void
    {
        $accounts = [
            ['code' => '1010', 'name' => 'Main Bank Account', 'type' => AccountType::BankAndCash],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => AccountType::Receivable],
            ['code' => '1300', 'name' => 'Inventory', 'type' => AccountType::CurrentAssets],
            ['code' => '2100', 'name' => 'Accounts Payable', 'type' => AccountType::Payable],
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => AccountType::Income],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => AccountType::CostOfRevenue],
        ];

        $this->createAccounts($company, $accounts);
    }

    protected function seedManufacturingTemplate(Company $company): void
    {
        $accounts = [
            ['code' => '1010', 'name' => 'Main Bank Account', 'type' => AccountType::BankAndCash],
            ['code' => '1310', 'name' => 'Raw Materials', 'type' => AccountType::CurrentAssets],
            ['code' => '1320', 'name' => 'Work in Progress', 'type' => AccountType::CurrentAssets],
            ['code' => '1330', 'name' => 'Finished Goods', 'type' => AccountType::CurrentAssets],
            ['code' => '4000', 'name' => 'Manufacturing Sales', 'type' => AccountType::Income],
        ];

        $this->createAccounts($company, $accounts);
    }

    protected function seedServicesTemplate(Company $company): void
    {
        $accounts = [
            ['code' => '1010', 'name' => 'Main Bank Account', 'type' => AccountType::BankAndCash],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => AccountType::Receivable],
            ['code' => '4000', 'name' => 'Service Revenue', 'type' => AccountType::Income],
            ['code' => '6000', 'name' => 'Labor Expense', 'type' => AccountType::Expense],
        ];

        $this->createAccounts($company, $accounts);
    }

    protected function seedGenericTemplate(Company $company): void
    {
        $accounts = [
            ['code' => '1010', 'name' => 'Bank', 'type' => AccountType::BankAndCash],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => AccountType::Receivable],
            ['code' => '2100', 'name' => 'Accounts Payable', 'type' => AccountType::Payable],
            ['code' => '4000', 'name' => 'Income', 'type' => AccountType::Income],
            ['code' => '6000', 'name' => 'Expenses', 'type' => AccountType::Expense],
        ];

        $this->createAccounts($company, $accounts);
    }

    protected function createAccounts(Company $company, array $accounts): void
    {
        foreach ($accounts as $acc) {
            $account = Account::firstOrCreate(
                ['company_id' => $company->id, 'code' => $acc['code']],
                [
                    'name' => ['en' => $acc['name']],
                    'type' => $acc['type'],
                ]
            );

            // Set default accounts on company if code matches common patterns
            if ($acc['code'] === '1010') {
                $company->update(['default_bank_account_id' => $account->id]);
            }
            if ($acc['code'] === '1200') {
                $company->update(['default_accounts_receivable_id' => $account->id]);
            }
            if ($acc['code'] === '2100') {
                $company->update(['default_accounts_payable_id' => $account->id]);
            }
        }
    }
}
