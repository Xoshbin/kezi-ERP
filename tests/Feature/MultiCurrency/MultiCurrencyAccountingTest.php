<?php

namespace Tests\Feature\MultiCurrency;

use App\Actions\Purchases\CreateVendorBillAction;
use App\Actions\Sales\CreateInvoiceAction;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\DataTransferObjects\Sales\CreateInvoiceDTO;
use App\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use App\Enums\Products\ProductType;
use App\Enums\Accounting\JournalType;
use App\Enums\Inventory\StockLocationType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\User;
use App\Services\InvoiceService;
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

/**
 * This test verifies the multi-currency accounting behavior according to Odoo's principles.
 *
 * EXPECTED BEHAVIOR (Odoo Principles):
 * - Company base currency: IQD
 * - All journal entries should be in company base currency (IQD)
 * - Foreign currency transactions should be converted using exchange rates
 * - Product costs should always be in company base currency
 *
 * FIXED BEHAVIOR:
 * - Vendor bill journal entries are now correctly created in company base currency (IQD) ✓
 * - Invoice journal entries are correctly created in company base currency (IQD) ✓
 * - General Ledger maintains consistency with all entries in base currency ✓
 *
 * EXCHANGE RATE: 1 USD = 1460 IQD
 */
test('multi-currency vendor bill and invoice create journal entries in company base currency following Odoo principles', function () {
    // Setup: Company with IQD base currency
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    // Create user
    $user = User::factory()->create();

    // Setup accounts for the company
    $stockInputAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Stock Input',
        'code' => '5100',
        'type' => 'expense'
    ]);

    $incomeAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Product Sales',
        'code' => '4000',
        'type' => 'income'
    ]);

    $apAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Accounts Payable',
        'code' => '2100',
        'type' => 'payable'
    ]);

    $arAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Accounts Receivable',
        'code' => '1200',
        'type' => 'receivable'
    ]);

    // Create journals
    $purchaseJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'name' => 'Purchase Journal',
        'type' => JournalType::Purchase,
        'short_code' => 'BILL',
        'currency_id' => $company->currency_id,
        'default_debit_account_id' => $stockInputAccount->id,
        'default_credit_account_id' => $apAccount->id,
    ]);

    $salesJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'name' => 'Sales Journal',
        'type' => JournalType::Sale,
        'short_code' => 'INV',
        'currency_id' => $company->currency_id,
        'default_debit_account_id' => $arAccount->id,
        'default_credit_account_id' => $incomeAccount->id,
    ]);

    // Create stock locations
    $vendorLocation = StockLocation::factory()->create([
        'company_id' => $company->id,
        'name' => 'Vendor Location',
        'type' => StockLocationType::Vendor,
        'is_active' => true,
    ]);

    $stockLocation = StockLocation::factory()->create([
        'company_id' => $company->id,
        'name' => 'Main Warehouse',
        'type' => StockLocationType::Internal,
        'is_active' => true,
    ]);

    // Configure company default accounts and locations
    $company->update([
        'default_accounts_payable_id' => $apAccount->id,
        'default_accounts_receivable_id' => $arAccount->id,
        'default_purchase_journal_id' => $purchaseJournal->id,
        'default_sales_journal_id' => $salesJournal->id,
        'default_vendor_location_id' => $vendorLocation->id,
        'default_stock_location_id' => $stockLocation->id,
    ]);

    // Create service product to avoid inventory observer issues
    // This allows us to focus on the journal entry currency conversion bug
    $product = Product::factory()->create([
        'company_id' => $company->id,
        'name' => 'Test Service',
        'type' => ProductType::Service, // Service product to avoid inventory calculations
        'unit_price' => Money::of(100, 'USD'), // Product price in USD
        'income_account_id' => $incomeAccount->id,
        'expense_account_id' => $stockInputAccount->id,
    ]);

    // Create vendor
    $vendor = Partner::factory()->vendor()->create([
        'company_id' => $company->id,
        'name' => 'USD Vendor'
    ]);

    // Create customer
    $customer = Partner::factory()->customer()->create([
        'company_id' => $company->id,
        'name' => 'USD Customer'
    ]);

    // Step 1: Create vendor bill in USD for $100
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
                description: 'Test Service - USD Purchase',
                quantity: 1,
                unit_price: Money::of(100, 'USD'), // $100 USD
                expense_account_id: $stockInputAccount->id,
                tax_id: null,
                analytic_account_id: null,
                currency: 'USD'
            )
        ],
        created_by_user_id: $user->id
    );

    $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDTO);

    // Verify vendor bill is created in USD
    expect($vendorBill->currency->code)->toBe('USD');
    expect($vendorBill->total_amount->getAmount()->toFloat())->toBe(100.0);

    // Step 2: Confirm/post the vendor bill
    app(VendorBillService::class)->confirm($vendorBill, $user);

    // Reload to get journal entry
    $vendorBill->refresh();

    // Step 3: Verify journal entry is created in company base currency (IQD)
    expect($vendorBill->journalEntry)->not->toBeNull();
    $journalEntry = $vendorBill->journalEntry;

    // FIXED: Journal entry is now correctly created in company base currency (IQD)
    expect($journalEntry->currency->code)->toBe('IQD', 'Journal entry must be in company base currency');

    // Calculate expected amount in IQD: $100 USD * 1460 = 146,000 IQD
    $expectedAmountIQD = Money::of(146000, 'IQD');

    // Verify journal entry lines are in IQD with correct converted amounts
    $journalEntry->load('lines');

    $debitLines = $journalEntry->lines->filter(fn($line) => $line->debit->isPositive());
    $creditLines = $journalEntry->lines->filter(fn($line) => $line->credit->isPositive());

    // Should have expense debit and accounts payable credit
    expect($debitLines)->toHaveCount(1);
    expect($creditLines)->toHaveCount(1);

    $expenseDebitLine = $debitLines->first();
    $apCreditLine = $creditLines->first();

    // FIXED: Amounts are now correctly converted to IQD
    expect($expenseDebitLine->debit->isEqualTo($expectedAmountIQD))->toBeTrue('Expense debit should be converted to IQD');
    expect($apCreditLine->credit->isEqualTo($expectedAmountIQD))->toBeTrue('AP credit should be converted to IQD');

    // Verify the accounts are correct
    expect($expenseDebitLine->account_id)->toBe($stockInputAccount->id);
    expect($apCreditLine->account_id)->toBe($apAccount->id);

    // Step 5: Create invoice in USD for the same product
    $invoiceDTO = new CreateInvoiceDTO(
        company_id: $company->id,
        customer_id: $customer->id,
        currency_id: $this->usd->id, // Invoice in USD
        invoice_date: now()->format('Y-m-d'),
        due_date: now()->addDays(30)->format('Y-m-d'),
        lines: [
            new CreateInvoiceLineDTO(
                description: 'Test Service - USD Sale',
                quantity: 1,
                unit_price: Money::of(150, 'USD'), // $150 USD sale price
                income_account_id: $incomeAccount->id,
                product_id: $product->id,
                tax_id: null
            )
        ],
        fiscal_position_id: null
    );

    $invoice = app(CreateInvoiceAction::class)->execute($invoiceDTO);

    // Verify invoice is created in USD
    expect($invoice->currency->code)->toBe('USD');
    expect($invoice->total_amount->getAmount()->toFloat())->toBe(150.0);

    // Step 6: Confirm/post the invoice
    app(InvoiceService::class)->confirm($invoice, $user);

    // Reload to get journal entry
    $invoice->refresh();

    // Step 7: Verify invoice journal entry is in company base currency (IQD)
    expect($invoice->journalEntry)->not->toBeNull();
    $invoiceJournalEntry = $invoice->journalEntry;

    // CORRECT BEHAVIOR: Invoice journal entry IS properly converted to company base currency (IQD)
    // This part of the system works correctly according to Odoo principles
    expect($invoiceJournalEntry->currency->code)->toBe('IQD');

    // Calculate expected amount in IQD: $150 USD * 1460 = 219,000 IQD
    $expectedInvoiceAmountIQD = Money::of(219000, 'IQD');

    // Verify journal entry lines are in IQD with correct amounts
    $invoiceJournalEntry->load('lines');
    $invoiceDebitLines = $invoiceJournalEntry->lines->filter(fn($line) => $line->debit->isPositive());
    $invoiceCreditLines = $invoiceJournalEntry->lines->filter(fn($line) => $line->credit->isPositive());

    // Should have AR debit and income credit
    expect($invoiceDebitLines)->toHaveCount(1);
    expect($invoiceCreditLines)->toHaveCount(1);

    $arDebitLine = $invoiceDebitLines->first();
    $incomeCreditLine = $invoiceCreditLines->first();

    // Verify amounts are converted to IQD (this works correctly)
    expect($arDebitLine->debit->isEqualTo($expectedInvoiceAmountIQD))->toBeTrue();
    expect($incomeCreditLine->credit->isEqualTo($expectedInvoiceAmountIQD))->toBeTrue();

    // Step 8: Verify GL consistency - all entries in company base currency
    // FIXED: All journal entries should be in company base currency (IQD)
    $allJournalEntries = \App\Models\JournalEntry::where('company_id', $company->id)->get();

    // Verify all entries are in IQD
    foreach ($allJournalEntries as $entry) {
        expect($entry->currency->code)->toBe('IQD', 'All journal entries must be in company base currency');
    }

    // Count entries by currency to verify consistency
    $iqdEntries = $allJournalEntries->where('currency.code', 'IQD')->count();
    $totalEntries = $allJournalEntries->count();

    // FIXED: All entries are now in IQD, maintaining GL consistency
    expect($iqdEntries)->toBe($totalEntries, 'All journal entries should be in company base currency (IQD)');
    expect($totalEntries)->toBeGreaterThan(0, 'Should have journal entries created');

    // This verifies the GL consistency according to Odoo's accounting principles
});
