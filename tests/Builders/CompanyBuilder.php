<?php

namespace Tests\Builders;

use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Currency;
use App\Models\StockLocation;
use App\Enums\Accounting\JournalType;
use App\Enums\Inventory\StockLocationType;

class CompanyBuilder
{
    protected ?Currency $currency = null;
    protected array $accounts = [];
    protected array $journals = [];
    protected array $stockLocations = [];
    protected bool $enableReconciliation = false;

    public static function new(): self
    {
        return new self();
    }

    public function withCurrency(string $code = 'IQD'): self
    {
        $this->currency = Currency::firstOrCreate(
            ['code' => $code],
            [
                'name'           => $code === 'IQD' ? 'Iraqi Dinar' : 'US Dollar',
                'symbol'         => $code,
                'is_active'      => true,
                'decimal_places' => $code === 'IQD' ? 3 : 2,
            ]
        );
        return $this;
    }

    public function withDefaultAccounts(): self
    {
        $this->accounts = [
            'default_accounts_payable_id' => ['type' => 'payable', 'name' => 'Accounts Payable'],
            'default_accounts_receivable_id' => ['type' => 'receivable', 'name' => 'Accounts Receivable'],
            'default_bank_account_id' => ['type' => 'bank_and_cash', 'name' => 'Bank'],
            'default_outstanding_receipts_account_id' => ['type' => 'current_assets', 'name' => 'Outstanding Receipts'],
            'default_sales_discount_account_id' => ['type' => 'expense', 'name' => 'Sales Discount'],
            'default_tax_account_id' => ['type' => 'current_liabilities', 'name' => 'Tax Payable'],
            'default_tax_receivable_id' => ['type' => 'current_assets', 'name' => 'Tax Receivable'],
            'default_gain_loss_account_id' => ['type' => 'income', 'name' => 'Gain/Loss on Asset Disposal'],

        ];
        return $this;
    }

    public function withDefaultJournals(): self
    {
        $this->journals = [
            'default_purchase_journal_id' => ['type' => JournalType::Purchase, 'name' => 'Purchase Journal'],
            'default_sales_journal_id' => ['type' => JournalType::Sale, 'name' => 'Sales Journal'],
            'default_bank_journal_id' => ['type' => JournalType::Bank, 'name' => 'Bank Journal'],
            'default_depreciation_journal_id' => ['type' => JournalType::Miscellaneous, 'name' => 'Depreciation Journal'],
            'default_purchase_journal_id' => ['type' => JournalType::Purchase, 'name' => 'Purchase Journal'],
            'default_sales_journal_id' => ['type' => JournalType::Sale, 'name' => 'Sales Journal'],
            'default_bank_journal_id' => ['type' => JournalType::Bank, 'name' => 'Bank Journal'],
            'default_depreciation_journal_id' => ['type' => JournalType::Miscellaneous, 'name' => 'Depreciation Journal'],
        ];
        return $this;
    }

    public function withDefaultStockLocations(): self
    {
        $this->stockLocations = [
            'default_stock_input_location_id' => ['name' => 'Stock Input', 'type' => StockLocationType::Vendor->value],
            'default_stock_output_location_id' => ['name' => 'Stock Output', 'type' => StockLocationType::Customer->value],
            'default_stock_location_id' => ['name' => 'Default Stock', 'type' => StockLocationType::Internal->value],
        ];
        return $this;
    }

    public function withReconciliationEnabled(): self
    {
        $this->enableReconciliation = true;
        return $this;
    }

    public function create(): Company
    {
        if (!$this->currency) {
            $this->withCurrency('IQD');
        }

        $company = Company::factory()->create([
            'currency_id' => $this->currency->id,
            'enable_reconciliation' => $this->enableReconciliation,
        ]);

        $accountInstances = [];
        foreach ($this->accounts as $key => $details) {
            $accountInstances[$key] = Account::factory()->for($company)->create($details);
        }

        $journalInstances = [];
        foreach ($this->journals as $key => $details) {
            $defaultAccount = $this->getDefaultAccountForJournal($key, $accountInstances);

            $journalInstances[$key] = Journal::factory()->for($company)->create(array_merge($details, [
                'default_debit_account_id' => $defaultAccount?->id,
                'default_credit_account_id' => $defaultAccount?->id,
            ]));
        }

        $locationInstances = [];
        foreach ($this->stockLocations as $key => $details) {
            $locationInstances[$key] = StockLocation::factory()->for($company)->create($details);
        }

        $company->update(array_merge(
            collect($accountInstances)->mapWithKeys(fn($acc, $k) => [$k => $acc->id])->all(),
            collect($journalInstances)->mapWithKeys(fn($jour, $k) => [$k => $jour->id])->all(),
            collect($locationInstances)->mapWithKeys(fn($loc, $k) => [$k => $loc->id])->all()
        ));

        // Associate the locations with the company
        if (isset($locationInstances['default_stock_input_location_id'])) {
            $company->vendorLocation()->associate($locationInstances['default_stock_input_location_id']);
        }
        if (isset($locationInstances['default_stock_location_id'])) {
            $company->defaultStockLocation()->associate($locationInstances['default_stock_location_id']);
        }
        $company->save();

        return $company->fresh();
    }

    private function getDefaultAccountForJournal(string $journalKey, array $accounts): ?Account
    {
        $mapping = [
            'default_sales_journal_id' => 'default_accounts_receivable_id',
            'default_purchase_journal_id' => 'default_accounts_payable_id',
            'default_bank_journal_id' => 'default_bank_account_id',
        ];

        // THIS IS THE FIX:
        // Check if the journal type has a mapping. If not (like for Depreciation), return null.
        if (!isset($mapping[$journalKey])) {
            return null;
        }

        return $accounts[$mapping[$journalKey]] ?? null;
    }
}
