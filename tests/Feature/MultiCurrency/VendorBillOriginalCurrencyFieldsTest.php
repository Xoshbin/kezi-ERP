<?php

namespace Tests\Feature\MultiCurrency;

use App\Actions\Purchases\CreateVendorBillAction;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\Enums\Accounting\JournalType;
use App\Enums\Inventory\StockLocationType;
use App\Enums\Products\ProductType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\User;
use App\Services\VendorBillService;
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

test('vendor bill journal entries store original currency amount and exchange rate for foreign currency transactions', function () {
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

    // Create journal and configure company
    $purchaseJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'name' => 'Purchase Journal',
        'type' => JournalType::Purchase,
        'currency_id' => $company->currency_id,
    ]);

    $company->update([
        'default_accounts_payable_id' => $apAccount->id,
        'default_purchase_journal_id' => $purchaseJournal->id,
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
    $vendorBillDTO = new CreateVendorBillDTO(
        company_id: $company->id,
        vendor_id: $vendor->id,
        currency_id: $this->usd->id, // Bill in USD
        bill_reference: 'BILL-USD-001',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: now()->addDays(30)->format('Y-m-d'),
        lines: [
            new CreateVendorBillLineDTO(
                product_id: $product->id,
                description: 'Consulting Service - USD',
                quantity: 1,
                unit_price: Money::of(100, 'USD'), // $100 USD
                expense_account_id: $expenseAccount->id,
                tax_id: null,
                analytic_account_id: null,
                currency: 'USD'
            )
        ],
        created_by_user_id: $user->id
    );

    $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDTO);

    // Confirm/post the vendor bill
    app(VendorBillService::class)->confirm($vendorBill, $user);

    // Reload to get journal entry
    $vendorBill->refresh();

    // Verify journal entry is created in base currency (IQD)
    expect($vendorBill->journalEntry)->not->toBeNull();
    $journalEntry = $vendorBill->journalEntry;
    expect($journalEntry->currency->code)->toBe('IQD');

    // Verify journal entry lines store original currency information
    $journalEntry->load('lines');
    expect($journalEntry->lines)->toHaveCount(2); // Expense debit + AP credit

    foreach ($journalEntry->lines as $line) {
        // EXPECTED: Original currency amount should be stored as Money object in USD
        expect($line->original_currency_amount)->not->toBeNull('Original currency amount must be stored');
        expect($line->original_currency_amount)->toBeInstanceOf(\Brick\Money\Money::class, 'Should be Money object');
        expect($line->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('USD', 'Original currency should be USD');
        expect($line->original_currency_amount->isEqualTo(Money::of(100, 'USD')))->toBeTrue('Original amount should be $100 USD');

        // EXPECTED: Original currency ID should be stored
        expect($line->original_currency_id)->not->toBeNull('Original currency ID must be stored');

        // EXPECTED: Exchange rate should be stored
        expect($line->exchange_rate_at_transaction)->toBe(1460.0, 'Exchange rate should be stored');
    }

    // Verify the debit line (expense)
    $debitLine = $journalEntry->lines->filter(fn($line) => $line->debit->isPositive())->first();
    expect($debitLine->account_id)->toBe($expenseAccount->id);
    expect($debitLine->debit->isEqualTo(Money::of(146000, 'IQD')))->toBeTrue('Debit should be converted to IQD');
    expect($debitLine->original_currency_amount->isEqualTo(Money::of(100, 'USD')))->toBeTrue('Original expense amount preserved');
    expect($debitLine->exchange_rate_at_transaction)->toBe(1460.0);

    // Verify the credit line (AP)
    $creditLine = $journalEntry->lines->filter(fn($line) => $line->credit->isPositive())->first();
    expect($creditLine->account_id)->toBe($apAccount->id);
    expect($creditLine->credit->isEqualTo(Money::of(146000, 'IQD')))->toBeTrue('Credit should be converted to IQD');
    expect($creditLine->original_currency_amount->isEqualTo(Money::of(100, 'USD')))->toBeTrue('Original AP amount preserved');
    expect($creditLine->exchange_rate_at_transaction)->toBe(1460.0);
});

test('vendor bill journal entries handle same currency correctly for original currency fields', function () {
    // Setup: Company with IQD base currency, vendor bill also in IQD
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();

    // Setup minimal required accounts and configuration
    $expenseAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);
    $apAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'payable']);

    $purchaseJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'type' => JournalType::Purchase,
        'currency_id' => $company->currency_id,
    ]);

    $company->update([
        'default_accounts_payable_id' => $apAccount->id,
        'default_purchase_journal_id' => $purchaseJournal->id,
    ]);

    $vendor = Partner::factory()->vendor()->create(['company_id' => $company->id]);
    $product = Product::factory()->create([
        'company_id' => $company->id,
        'type' => ProductType::Service,
        'expense_account_id' => $expenseAccount->id,
    ]);

    // Create vendor bill in IQD (same as company currency)
    $vendorBillDTO = new CreateVendorBillDTO(
        company_id: $company->id,
        vendor_id: $vendor->id,
        currency_id: $this->iqd->id, // Same currency as company
        bill_reference: 'BILL-IQD-001',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: now()->addDays(30)->format('Y-m-d'),
        lines: [
            new CreateVendorBillLineDTO(
                product_id: $product->id,
                description: 'Service - IQD',
                quantity: 1,
                unit_price: Money::of(146000, 'IQD'), // 146,000 IQD
                expense_account_id: $expenseAccount->id,
                tax_id: null,
                analytic_account_id: null,
                currency: 'IQD'
            )
        ],
        created_by_user_id: $user->id
    );

    $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDTO);
    app(VendorBillService::class)->confirm($vendorBill, $user);

    $vendorBill->refresh();
    $journalEntry = $vendorBill->journalEntry;

    // Verify same currency handling
    expect($journalEntry->currency->code)->toBe('IQD');

    foreach ($journalEntry->lines as $line) {
        // For same currency, original amount should equal the IQD amount as Money object
        expect($line->original_currency_amount)->toBeInstanceOf(\Brick\Money\Money::class, 'Should be Money object');
        expect($line->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('IQD', 'Original currency should be IQD');
        expect($line->original_currency_amount->isEqualTo(Money::of(146000, 'IQD')))->toBeTrue('Original amount should be 146,000 IQD');
        expect($line->exchange_rate_at_transaction)->toBe(1.0, 'Exchange rate should be 1.0 for same currency');
    }
});
