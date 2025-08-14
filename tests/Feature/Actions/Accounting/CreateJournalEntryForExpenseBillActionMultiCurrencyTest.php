<?php

namespace Tests\Feature\Actions\Accounting;

use App\Actions\Accounting\CreateJournalEntryForExpenseBillAction;
use App\Enums\Products\ProductType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorBill;
use App\Models\VendorBillLine;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    // Create currencies with proper exchange rates
    $this->iqd = Currency::firstOrCreate(
        ['code' => 'IQD'],
        [
            'name' => 'Iraqi Dinar',
            'symbol' => 'IQD',
            'exchange_rate' => 1.0, // Base currency
            'is_active' => true,
            'decimal_places' => 3
        ]
    );

    $this->usd = Currency::firstOrCreate(
        ['code' => 'USD'],
        [
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1460.0, // 1 USD = 1460 IQD
            'is_active' => true,
            'decimal_places' => 2
        ]
    );
});

test('creates journal entry in company base currency for foreign currency vendor bill', function () {
    // Setup: Company with IQD base currency
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();

    // Setup accounts
    $expenseAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Office Expenses',
        'code' => '5100',
        'type' => 'expense'
    ]);

    $apAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Accounts Payable',
        'code' => '2100',
        'type' => 'payable'
    ]);

    // Configure company default accounts
    $company->update([
        'default_accounts_payable_id' => $apAccount->id,
        'default_purchase_journal_id' => 1, // Assume journal exists
    ]);

    // Create vendor
    $vendor = Partner::factory()->vendor()->create([
        'company_id' => $company->id,
        'name' => 'USD Vendor'
    ]);

    // Create service product
    $product = Product::factory()->create([
        'company_id' => $company->id,
        'name' => 'Consulting Service',
        'type' => ProductType::Service,
        'expense_account_id' => $expenseAccount->id,
    ]);

    // Create vendor bill in USD
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $company->id,
        'vendor_id' => $vendor->id,
        'currency_id' => $this->usd->id, // Bill in USD
        'bill_reference' => 'BILL-USD-001',
        'total_amount' => Money::of(100, 'USD'),
    ]);

    // Create vendor bill line in USD
    VendorBillLine::factory()->create([
        'vendor_bill_id' => $vendorBill->id,
        'product_id' => $product->id,
        'description' => 'Consulting Service - USD',
        'quantity' => 1,
        'unit_price' => Money::of(100, 'USD'), // $100 USD
        'subtotal' => Money::of(100, 'USD'),
        'total_line_tax' => Money::of(0, 'USD'),
        'expense_account_id' => $expenseAccount->id,
    ]);

    // Execute the action
    $action = app(CreateJournalEntryForExpenseBillAction::class);
    $journalEntry = $action->execute($vendorBill, $user);

    // EXPECTED BEHAVIOR: Journal entry should be in company base currency (IQD)
    expect($journalEntry->currency->code)->toBe('IQD', 'Journal entry must be in company base currency');

    // Calculate expected amount in IQD: $100 USD * 1460 = 146,000 IQD
    $expectedAmountIQD = Money::of(146000, 'IQD');

    // Verify journal entry lines are in IQD with correct converted amounts
    $journalEntry->load('lines');
    $debitLines = $journalEntry->lines->filter(fn($line) => $line->debit->isPositive());
    $creditLines = $journalEntry->lines->filter(fn($line) => $line->credit->isPositive());

    expect($debitLines)->toHaveCount(1, 'Should have one expense debit line');
    expect($creditLines)->toHaveCount(1, 'Should have one AP credit line');

    $expenseDebitLine = $debitLines->first();
    $apCreditLine = $creditLines->first();

    // Verify amounts are converted to IQD
    expect($expenseDebitLine->debit->isEqualTo($expectedAmountIQD))->toBeTrue('Expense debit should be converted to IQD');
    expect($apCreditLine->credit->isEqualTo($expectedAmountIQD))->toBeTrue('AP credit should be converted to IQD');

    // Verify accounts are correct
    expect($expenseDebitLine->account_id)->toBe($expenseAccount->id);
    expect($apCreditLine->account_id)->toBe($apAccount->id);

    // Verify journal entry totals are balanced in IQD
    expect($journalEntry->total_debit->isEqualTo($expectedAmountIQD))->toBeTrue();
    expect($journalEntry->total_credit->isEqualTo($expectedAmountIQD))->toBeTrue();
});

test('handles same currency vendor bill correctly', function () {
    // Setup: Company with IQD base currency, vendor bill also in IQD
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();

    // Setup accounts
    $expenseAccount = Account::factory()->create([
        'company_id' => $company->id,
        'type' => 'expense'
    ]);

    $apAccount = Account::factory()->create([
        'company_id' => $company->id,
        'type' => 'payable'
    ]);

    $company->update([
        'default_accounts_payable_id' => $apAccount->id,
        'default_purchase_journal_id' => 1,
    ]);

    $vendor = Partner::factory()->vendor()->create(['company_id' => $company->id]);
    $product = Product::factory()->create([
        'company_id' => $company->id,
        'type' => ProductType::Service,
        'expense_account_id' => $expenseAccount->id,
    ]);

    // Create vendor bill in IQD (same as company currency)
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $company->id,
        'vendor_id' => $vendor->id,
        'currency_id' => $this->iqd->id, // Same currency
        'total_amount' => Money::of(146000, 'IQD'),
    ]);

    VendorBillLine::factory()->create([
        'vendor_bill_id' => $vendorBill->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => Money::of(146000, 'IQD'),
        'subtotal' => Money::of(146000, 'IQD'),
        'total_line_tax' => Money::of(0, 'IQD'),
        'expense_account_id' => $expenseAccount->id,
    ]);

    // Execute the action
    $action = app(CreateJournalEntryForExpenseBillAction::class);
    $journalEntry = $action->execute($vendorBill, $user);

    // Should remain in IQD with no conversion
    expect($journalEntry->currency->code)->toBe('IQD');
    
    $expectedAmount = Money::of(146000, 'IQD');
    expect($journalEntry->total_debit->isEqualTo($expectedAmount))->toBeTrue();
    expect($journalEntry->total_credit->isEqualTo($expectedAmount))->toBeTrue();
});
