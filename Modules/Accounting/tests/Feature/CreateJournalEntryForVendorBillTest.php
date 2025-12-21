<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Actions\Accounting\CreateJournalEntryForVendorBillAction;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\CurrencyRate;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Models\VendorBillLine;

uses(RefreshDatabase::class);

test('correctly creates journal entry for vendor bill with currency conversion', function () {
    // 1. Setup Currencies
    $iqd = Currency::factory()->create(['code' => 'IQD', 'decimal_places' => 3]); // Base
    $usd = Currency::factory()->create(['code' => 'USD', 'decimal_places' => 2]);

    // 2. Setup Company with IQD base
    $company = Company::factory()->create([
        'currency_id' => $iqd->id,
        'name' => 'IQD Company',
    ]);

    // Setup Accounts
    $apAccount = Account::factory()->create(['company_id' => $company->id, 'code' => '200000', 'name' => 'AP']);
    $expenseAccount = Account::factory()->create(['company_id' => $company->id, 'code' => '600000', 'name' => 'Expense']);
    $taxAccount = Account::factory()->create(['company_id' => $company->id, 'code' => '100000', 'name' => 'Tax']);

    $company->update([
        'default_accounts_payable_id' => $apAccount->id,
        'default_tax_account_id' => $taxAccount->id,
        'default_tax_receivable_id' => $taxAccount->id,
    ]);

    $journal = Journal::factory()->create(['company_id' => $company->id, 'type' => 'purchase']);
    $company->update(['default_purchase_journal_id' => $journal->id]);

    // 3. Setup Vendor Bill
    $vendor = \Modules\Foundation\Models\Partner::factory()->create(['company_id' => $company->id, 'payable_account_id' => $apAccount->id]);

    $bill = VendorBill::create([
        'company_id' => $company->id,
        'vendor_id' => $vendor->id,
        'currency_id' => $usd->id, // Bill in USD
        'bill_date' => Carbon::today(),
        'due_date' => Carbon::today(),
        'bill_reference' => 'BILL-TEST-001',
        'accounting_date' => Carbon::today(),
        'status' => 'draft',
        'exchange_rate_at_creation' => 1460.0, // Fixed Rate
        'total_amount' => Money::of(1000, 'USD'), // 1000 USD
        'total_tax' => Money::of(0, 'USD'),
    ]);

    VendorBillLine::create([
        'vendor_bill_id' => $bill->id,
        'product_id' => null,
        'description' => 'Test Expense',
        'quantity' => 1,
        'unit_price' => Money::of(1000, 'USD'),
        'subtotal' => Money::of(1000, 'USD'),
        'total_line_tax' => Money::of(0, 'USD'),
        'expense_account_id' => $expenseAccount->id,
        'company_id' => $company->id,
    ]);

    // 4. Run the Action
    $user = User::factory()->create();

    // Create exchange rate in DB too
    CurrencyRate::create([
        'company_id' => $company->id,
        'currency_id' => $usd->id,
        'rate' => 1460,
        'effective_date' => Carbon::today(),
    ]);

    $action = app(CreateJournalEntryForVendorBillAction::class);
    $journalEntry = $action->execute($bill, $user);

    // 5. Verification
    // Journal Entry should be in Company Currency (IQD)
    expect($journalEntry->currency_id)->toBe($iqd->id);

    // Total Debit should be 1,460,000 IQD
    $totalDebit = $journalEntry->total_debit;

    expect($totalDebit->getAmount()->toFloat())->toBe(1460000.0);
});
