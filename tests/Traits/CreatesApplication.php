<?php

namespace Tests\Traits;

use App\Models\Company;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function createConfiguredCompany(): Company
    {
        $currency = \Modules\Foundation\Models\Currency::firstOrCreate(['code' => 'IQD'], ['name' => 'Iraqi Dinar', 'symbol' => 'IQD', 'exchange_rate' => 1, 'is_active' => true, 'decimal_places' => 3]);
        $company = Company::factory()->create(['currency_id' => $currency->id]);

        // Create all necessary accounts first.
        $accounts = [
            'default_accounts_payable_id' => \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => 'Liability', 'name' => 'Accounts Payable']),
            'default_tax_receivable_id' => \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => 'Asset', 'name' => 'Tax Receivable']),
            'default_accounts_receivable_id' => \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => 'Asset', 'name' => 'Accounts Receivable']),
            'default_sales_discount_account_id' => \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => 'Expense', 'name' => 'Sales Discount']),
            'default_tax_account_id' => \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => 'Liability', 'name' => 'Tax Payable']),
            'default_bank_account_id' => \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => 'bank_and_cash', 'name' => 'Bank']),
            'default_outstanding_receipts_account_id' => \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => 'current_assets', 'name' => 'Outstanding Receipts']),
        ];

        $accountIds = collect($accounts)->mapWithKeys(fn($account, $key) => [$key => $account->id])->all();

        // Now, create journals and link them to the *already created* default accounts.
        $journals = [
            'default_purchase_journal_id' => Journal::factory()->for($company)->create([
                'type' => 'Purchase',
                'name' => 'Purchase Journal',
                'default_debit_account_id' => $accounts['default_accounts_payable_id']->id,
                'default_credit_account_id' => $accounts['default_accounts_payable_id']->id,
            ]),
            'default_sales_journal_id' => Journal::factory()->for($company)->create([
                'type' => 'Sale',
                'name' => 'Sales Journal',
                'default_debit_account_id' => $accounts['default_accounts_receivable_id']->id,
                'default_credit_account_id' => $accounts['default_accounts_receivable_id']->id,
            ]),
            'default_depreciation_journal_id' => Journal::factory()->for($company)->create([
                'type' => 'General',
                'name' => 'Depreciation Journal',
            ]),
            'default_bank_journal_id' => Journal::factory()->for($company)->create([
                'type' => 'Bank',
                'name' => 'Bank Journal',
                'default_debit_account_id' => $accounts['default_bank_account_id']->id,
                'default_credit_account_id' => $accounts['default_bank_account_id']->id,
            ]),
        ];
        $journalIds = collect($journals)->mapWithKeys(fn($journal, $key) => [$key => $journal->id])->all();

        // Update the company with all IDs in a single, atomic operation.
        $company->update(array_merge($accountIds, $journalIds));

        // Refresh the model from the database to ensure all relationships are loaded.
        return $company->fresh();
    }
}
