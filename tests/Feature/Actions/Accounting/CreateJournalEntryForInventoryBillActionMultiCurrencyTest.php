<?php

namespace Tests\Feature\Actions\Accounting;

use App\Actions\Accounting\CreateJournalEntryForInventoryBillAction;
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

test('creates inventory journal entry in company base currency for foreign currency vendor bill', function () {
    // Setup: Company with IQD base currency
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();

    // Setup accounts
    $inventoryAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Inventory',
        'code' => '1300',
        'type' => 'current_assets'
    ]);

    $stockInputAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Stock Input',
        'code' => '5100',
        'type' => 'expense'
    ]);

    // Configure company default accounts
    $company->update([
        'default_purchase_journal_id' => 1, // Assume journal exists
    ]);

    // Create vendor
    $vendor = Partner::factory()->vendor()->create([
        'company_id' => $company->id,
        'name' => 'USD Vendor'
    ]);

    // Create storable product
    $product = Product::factory()->create([
        'company_id' => $company->id,
        'name' => 'Test Product',
        'type' => ProductType::Storable,
        'default_inventory_account_id' => $inventoryAccount->id,
        'default_stock_input_account_id' => $stockInputAccount->id,
    ]);

    // Create vendor bill in USD
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $company->id,
        'vendor_id' => $vendor->id,
        'currency_id' => $this->usd->id, // Bill in USD
        'bill_reference' => 'BILL-USD-001',
        'total_amount' => Money::of(100, 'USD'),
    ]);

    // Create vendor bill line in USD for storable product
    VendorBillLine::factory()->create([
        'vendor_bill_id' => $vendorBill->id,
        'product_id' => $product->id,
        'description' => 'Test Product - USD',
        'quantity' => 1,
        'unit_price' => Money::of(100, 'USD'), // $100 USD
        'subtotal' => Money::of(100, 'USD'),
        'total_line_tax' => Money::of(0, 'USD'),
        'expense_account_id' => $stockInputAccount->id,
    ]);

    // Execute the action
    $action = app(CreateJournalEntryForInventoryBillAction::class);
    $journalEntry = $action->execute($vendorBill, $user);

    // EXPECTED BEHAVIOR: Journal entry should be in company base currency (IQD)
    expect($journalEntry->currency->code)->toBe('IQD', 'Journal entry must be in company base currency');

    // Calculate expected amount in IQD: $100 USD * 1460 = 146,000 IQD
    $expectedAmountIQD = Money::of(146000, 'IQD');

    // Verify journal entry lines are in IQD with correct converted amounts
    $journalEntry->load('lines');
    $debitLines = $journalEntry->lines->filter(fn($line) => $line->debit->isPositive());
    $creditLines = $journalEntry->lines->filter(fn($line) => $line->credit->isPositive());

    expect($debitLines)->toHaveCount(1, 'Should have one inventory debit line');
    expect($creditLines)->toHaveCount(1, 'Should have one stock input credit line');

    $inventoryDebitLine = $debitLines->first();
    $stockInputCreditLine = $creditLines->first();

    // Verify amounts are converted to IQD
    expect($inventoryDebitLine->debit->isEqualTo($expectedAmountIQD))->toBeTrue('Inventory debit should be converted to IQD');
    expect($stockInputCreditLine->credit->isEqualTo($expectedAmountIQD))->toBeTrue('Stock input credit should be converted to IQD');

    // Verify accounts are correct
    expect($inventoryDebitLine->account_id)->toBe($inventoryAccount->id);
    expect($stockInputCreditLine->account_id)->toBe($stockInputAccount->id);

    // Verify journal entry totals are balanced in IQD
    expect($journalEntry->total_debit->isEqualTo($expectedAmountIQD))->toBeTrue();
    expect($journalEntry->total_credit->isEqualTo($expectedAmountIQD))->toBeTrue();
});

test('handles same currency inventory bill correctly', function () {
    // Setup: Company with IQD base currency, vendor bill also in IQD
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();

    // Setup accounts
    $inventoryAccount = Account::factory()->create([
        'company_id' => $company->id,
        'type' => 'current_assets'
    ]);

    $stockInputAccount = Account::factory()->create([
        'company_id' => $company->id,
        'type' => 'expense'
    ]);

    $company->update([
        'default_purchase_journal_id' => 1,
    ]);

    $vendor = Partner::factory()->vendor()->create(['company_id' => $company->id]);
    $product = Product::factory()->create([
        'company_id' => $company->id,
        'type' => ProductType::Storable,
        'default_inventory_account_id' => $inventoryAccount->id,
        'default_stock_input_account_id' => $stockInputAccount->id,
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
        'expense_account_id' => $stockInputAccount->id,
    ]);

    // Execute the action
    $action = app(CreateJournalEntryForInventoryBillAction::class);
    $journalEntry = $action->execute($vendorBill, $user);

    // Should remain in IQD with no conversion
    expect($journalEntry->currency->code)->toBe('IQD');
    
    $expectedAmount = Money::of(146000, 'IQD');
    expect($journalEntry->total_debit->isEqualTo($expectedAmount))->toBeTrue();
    expect($journalEntry->total_credit->isEqualTo($expectedAmount))->toBeTrue();
});
