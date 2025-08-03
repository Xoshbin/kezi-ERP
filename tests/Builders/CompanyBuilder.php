<?php

namespace Tests\Builders;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Account;
use App\Models\Journal;

class CompanyBuilder
{
    protected ?Currency $currency = null;
    protected array $accounts = [];
    protected array $journals = [];

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
                'exchange_rate'  => 1,
                'is_active'      => true,
                'decimal_places' => $code === 'IQD' ? 3 : 2,
            ]
        );
        return $this;
    }

    public function withDefaultAccounts(): self
    {
        $this->accounts = [
            'default_accounts_payable_id' => ['type' => 'Liability', 'name' => 'Accounts Payable'],
            'default_accounts_receivable_id' => ['type' => 'Asset', 'name' => 'Accounts Receivable'],
            'default_bank_account_id' => ['type' => 'Asset', 'name' => 'Bank'],
            'default_outstanding_receipts_account_id' => ['type' => 'Asset', 'name' => 'Outstanding Receipts'],
            'default_sales_discount_account_id' => ['type' => 'Expense', 'name' => 'Sales Discount'],
            'default_tax_account_id' => ['type' => 'Liability', 'name' => 'Tax Payable'],
            'default_tax_receivable_id' => ['type' => 'Asset', 'name' => 'Tax Receivable'],
        ];
        return $this;
    }

    public function withDefaultJournals(): self
    {
        $this->journals = [
            'default_purchase_journal_id' => ['type' => 'Purchase', 'name' => 'Purchase Journal'],
            'default_sales_journal_id' => ['type' => 'Sale', 'name' => 'Sales Journal'],
            'default_bank_journal_id' => ['type' => 'Bank', 'name' => 'Bank Journal'],
            'default_depreciation_journal_id' => ['type' => 'General', 'name' => 'Depreciation Journal'],
        ];
        return $this;
    }

    public function create(): Company
    {
        if (!$this->currency) {
            $this->withCurrency('IQD');
        }

        $company = Company::factory()->create(['currency_id' => $this->currency->id]);

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

        $company->update(array_merge(
            collect($accountInstances)->mapWithKeys(fn($acc, $k) => [$k => $acc->id])->all(),
            collect($journalInstances)->mapWithKeys(fn($jour, $k) => [$k => $jour->id])->all()
        ));

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
