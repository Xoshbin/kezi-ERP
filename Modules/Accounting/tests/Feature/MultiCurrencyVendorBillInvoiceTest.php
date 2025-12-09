<?php

namespace Modules\Accounting\Tests\Feature;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Account;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\CurrencyRate;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Modules\Purchase\Actions\Purchases\CreateVendorBillAction;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillDTO;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Modules\Purchase\Services\VendorBillService;
use Modules\Sales\DataTransferObjects\Sales\CreateInvoiceDTO;
use Modules\Sales\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use Modules\Sales\Services\InvoiceService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    // Create USD currency for foreign currency tests
    $this->usdCurrency = Currency::firstOrCreate(
        ['code' => 'USD'],
        [
            'name' => ['en' => 'US Dollar', 'ckb' => 'دۆلاری ئەمریکی', 'ar' => 'دولار أمريكي'],
            'symbol' => '$',
            'is_active' => true,
            'decimal_places' => 2,
        ]
    );

    // Set up exchange rate: 1 USD = 1460 IQD
    $this->exchangeRate = 1460.0;
    $this->transactionDate = Carbon::parse('2024-01-01');

    CurrencyRate::updateOrCreate(
        [
            'currency_id' => $this->usdCurrency->id,
            'effective_date' => $this->transactionDate->toDateString(),
            'company_id' => $this->company->id,
        ],
        [
            'rate' => $this->exchangeRate,
            'source' => 'manual',
        ]
    );

    // Create test vendor and customer
    $this->vendor = Partner::factory()->for($this->company)->create(['type' => \Modules\Foundation\Enums\Partners\PartnerType::Vendor]);
    $this->customer = Partner::factory()->for($this->company)->create(['type' => \Modules\Foundation\Enums\Partners\PartnerType::Customer]);

    // Create test accounts first
    $this->expenseAccount = Account::factory()->for($this->company)->create(['type' => 'expense']);
    $this->incomeAccount = Account::factory()->for($this->company)->create(['type' => 'income']);
    $this->inventoryAccount = Account::factory()->for($this->company)->create(['type' => 'current_assets']);
    $this->stockInputAccount = Account::factory()->for($this->company)->create(['type' => 'current_assets']);

    // Create test product as service (non-storable) to avoid inventory complications
    $this->product = Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Service, // Use service type to avoid inventory movements
        'expense_account_id' => $this->expenseAccount->id,
        'income_account_id' => $this->incomeAccount->id,
    ]);
});

describe('VendorBill Multi-Currency Tests', function () {
    test('can create vendor bill in USD with proper dual currency storage', function () {
        // Arrange: Create vendor bill in USD
        $vendorBillDto = new CreateVendorBillDTO(
            company_id: $this->company->id,
            vendor_id: $this->vendor->id,
            currency_id: $this->usdCurrency->id,
            bill_reference: 'USD-BILL-001',
            bill_date: $this->transactionDate->toDateString(),
            accounting_date: $this->transactionDate->toDateString(),
            due_date: $this->transactionDate->addDays(30)->toDateString(),
            lines: [
                new CreateVendorBillLineDTO(
                    product_id: $this->product->id,
                    description: 'Test Product in USD',
                    quantity: 2,
                    unit_price: Money::of(100, 'USD'), // $100.00
                    expense_account_id: $this->expenseAccount->id,
                    tax_id: null,
                    analytic_account_id: null,
                    currency: 'USD'
                ),
            ],
            created_by_user_id: $this->user->id
        );

        // Act: Create the vendor bill
        $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDto);
        $vendorBill->refresh();

        // Assert: Verify dual currency storage
        // Document currency amounts (what vendor sees)
        expect($vendorBill->currency_id)->toBe($this->usdCurrency->id);
        expect($vendorBill->total_amount->getCurrency()->getCurrencyCode())->toBe('USD');
        expect($vendorBill->total_amount->getAmount()->toFloat())->toBe(200.0); // 2 * $100

        // Base currency amounts should be null before posting
        expect($vendorBill->total_amount_company_currency)->toBeNull();
        expect($vendorBill->exchange_rate_at_creation)->toBeNull();

        // Line level verification
        $line = $vendorBill->lines->first();
        expect($line->unit_price->getCurrency()->getCurrencyCode())->toBe('USD');
        expect($line->unit_price->getAmount()->toFloat())->toBe(100.0);
        expect($line->subtotal->getAmount()->toFloat())->toBe(200.0);

        // Company currency fields should be null before posting
        expect($line->unit_price_company_currency)->toBeNull();
        expect($line->subtotal_company_currency)->toBeNull();
    });

    test('posting USD vendor bill converts amounts to base currency', function () {
        // Arrange: Create and post vendor bill in USD
        $vendorBillDto = new CreateVendorBillDTO(
            company_id: $this->company->id,
            vendor_id: $this->vendor->id,
            currency_id: $this->usdCurrency->id,
            bill_reference: 'USD-BILL-002',
            bill_date: $this->transactionDate->toDateString(),
            accounting_date: $this->transactionDate->toDateString(),
            due_date: $this->transactionDate->addDays(30)->toDateString(),
            lines: [
                new CreateVendorBillLineDTO(
                    product_id: $this->product->id,
                    description: 'Test Product in USD',
                    quantity: 1,
                    unit_price: Money::of(100, 'USD'), // $100.00
                    expense_account_id: $this->expenseAccount->id,
                    tax_id: null,
                    analytic_account_id: null,
                    currency: 'USD'
                ),
            ],
            created_by_user_id: $this->user->id
        );

        $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDto);

        // Act: Post the vendor bill
        app(VendorBillService::class)->post($vendorBill, $this->user);
        $vendorBill->refresh();

        // Assert: Verify currency conversion after posting
        // Document currency amounts should remain unchanged
        expect($vendorBill->total_amount->getCurrency()->getCurrencyCode())->toBe('USD');
        expect($vendorBill->total_amount->getAmount()->toFloat())->toBe(100.0);

        // Base currency amounts should be calculated
        expect((float) $vendorBill->exchange_rate_at_creation)->toBe($this->exchangeRate);
        expect($vendorBill->total_amount_company_currency->getCurrency()->getCurrencyCode())->toBe('IQD');
        expect($vendorBill->total_amount_company_currency->getAmount()->toFloat())->toBe(146000.0); // $100 * 1460

        // Line level verification
        $line = $vendorBill->lines->first();
        expect($line->unit_price_company_currency->getCurrency()->getCurrencyCode())->toBe('IQD');
        expect($line->unit_price_company_currency->getAmount()->toFloat())->toBe(146000.0);
        expect($line->subtotal_company_currency->getAmount()->toFloat())->toBe(146000.0);

        // Journal entry should use base currency amounts
        expect($vendorBill->journalEntry)->not->toBeNull();
        expect($vendorBill->journalEntry->total_debit->getCurrency()->getCurrencyCode())->toBe('IQD');
        expect($vendorBill->journalEntry->total_debit->getAmount()->toFloat())->toBe(146000.0);
    });

    test('vendor bill in base currency IQD works correctly', function () {
        // Arrange: Create vendor bill in base currency (IQD)
        $vendorBillDto = new CreateVendorBillDTO(
            company_id: $this->company->id,
            vendor_id: $this->vendor->id,
            currency_id: $this->company->currency_id,
            bill_reference: 'IQD-BILL-001',
            bill_date: $this->transactionDate->toDateString(),
            accounting_date: $this->transactionDate->toDateString(),
            due_date: $this->transactionDate->addDays(30)->toDateString(),
            lines: [
                new CreateVendorBillLineDTO(
                    product_id: $this->product->id,
                    description: 'Test Product in IQD',
                    quantity: 1,
                    unit_price: Money::of(50000, 'IQD'), // 50,000 IQD
                    expense_account_id: $this->expenseAccount->id,
                    tax_id: null,
                    analytic_account_id: null,
                    currency: 'IQD'
                ),
            ],
            created_by_user_id: $this->user->id
        );

        $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDto);

        // Act: Post the vendor bill
        app(VendorBillService::class)->post($vendorBill, $this->user);
        $vendorBill->refresh();

        // Assert: For base currency, both amounts should be the same
        expect((float) $vendorBill->exchange_rate_at_creation)->toBe(1.0);
        expect($vendorBill->total_amount->getAmount()->toFloat())->toBe(50000.0);
        expect($vendorBill->total_amount_company_currency->getAmount()->toFloat())->toBe(50000.0);

        // Both should be in IQD
        expect($vendorBill->total_amount->getCurrency()->getCurrencyCode())->toBe('IQD');
        expect($vendorBill->total_amount_company_currency->getCurrency()->getCurrencyCode())->toBe('IQD');
    });
});

describe('Invoice Multi-Currency Tests', function () {
    test('can create invoice in USD with proper dual currency storage', function () {
        // Arrange: Create invoice in USD
        $invoiceDto = new CreateInvoiceDTO(
            company_id: $this->company->id,
            customer_id: $this->customer->id,
            currency_id: $this->usdCurrency->id,
            invoice_date: $this->transactionDate->toDateString(),
            due_date: $this->transactionDate->addDays(30)->toDateString(),
            lines: [
                new CreateInvoiceLineDTO(
                    description: 'Test Product in USD',
                    quantity: 3,
                    unit_price: Money::of(150, 'USD'), // $150.00
                    income_account_id: $this->incomeAccount->id,
                    product_id: $this->product->id,
                    tax_id: null
                ),
            ],
            fiscal_position_id: null
        );

        // Act: Create the invoice
        $invoice = app(\Modules\Sales\Actions\Sales\CreateInvoiceAction::class)->execute($invoiceDto);
        $invoice->refresh();

        // Assert: Verify dual currency storage
        // Document currency amounts (what customer sees)
        expect($invoice->currency_id)->toBe($this->usdCurrency->id);
        expect($invoice->total_amount->getCurrency()->getCurrencyCode())->toBe('USD');
        expect($invoice->total_amount->getAmount()->toFloat())->toBe(450.0); // 3 * $150

        // Base currency amounts should be null before posting
        expect($invoice->total_amount_company_currency)->toBeNull();
        expect($invoice->exchange_rate_at_creation)->toBeNull();

        // Line level verification
        $line = $invoice->invoiceLines->first();
        expect($line->unit_price->getCurrency()->getCurrencyCode())->toBe('USD');
        expect($line->unit_price->getAmount()->toFloat())->toBe(150.0);
        expect($line->subtotal->getAmount()->toFloat())->toBe(450.0);

        // Company currency fields should be null before posting
        expect($line->unit_price_company_currency)->toBeNull();
        expect($line->subtotal_company_currency)->toBeNull();
    });

    test('posting USD invoice converts amounts to base currency', function () {
        // Arrange: Create invoice in USD
        $invoiceDto = new CreateInvoiceDTO(
            company_id: $this->company->id,
            customer_id: $this->customer->id,
            currency_id: $this->usdCurrency->id,
            invoice_date: $this->transactionDate->toDateString(),
            due_date: $this->transactionDate->addDays(30)->toDateString(),
            lines: [
                new CreateInvoiceLineDTO(
                    description: 'Test Product in USD',
                    quantity: 1,
                    unit_price: Money::of(200, 'USD'), // $200.00
                    income_account_id: $this->incomeAccount->id,
                    product_id: $this->product->id,
                    tax_id: null
                ),
            ],
            fiscal_position_id: null
        );

        $invoice = app(\Modules\Sales\Actions\Sales\CreateInvoiceAction::class)->execute($invoiceDto);

        // Act: Post the invoice
        app(InvoiceService::class)->confirm($invoice, $this->user);
        $invoice->refresh();

        // Assert: Verify currency conversion after posting
        // Document currency amounts should remain unchanged
        expect($invoice->total_amount->getCurrency()->getCurrencyCode())->toBe('USD');
        expect($invoice->total_amount->getAmount()->toFloat())->toBe(200.0);

        // Base currency amounts should be calculated
        expect((float) $invoice->exchange_rate_at_creation)->toBe($this->exchangeRate);
        expect($invoice->total_amount_company_currency->getCurrency()->getCurrencyCode())->toBe('IQD');
        expect($invoice->total_amount_company_currency->getAmount()->toFloat())->toBe(292000.0); // $200 * 1460

        // Line level verification
        $line = $invoice->invoiceLines->first();
        expect($line->unit_price_company_currency->getCurrency()->getCurrencyCode())->toBe('IQD');
        expect($line->unit_price_company_currency->getAmount()->toFloat())->toBe(292000.0);
        expect($line->subtotal_company_currency->getAmount()->toFloat())->toBe(292000.0);

        // Journal entry should use base currency amounts
        expect($invoice->journalEntry)->not->toBeNull();
        expect($invoice->journalEntry->total_debit->getCurrency()->getCurrencyCode())->toBe('IQD');
        expect($invoice->journalEntry->total_debit->getAmount()->toFloat())->toBe(292000.0);
    });

    test('invoice in base currency IQD works correctly', function () {
        // Arrange: Create invoice in base currency (IQD)
        $invoiceDto = new CreateInvoiceDTO(
            company_id: $this->company->id,
            customer_id: $this->customer->id,
            currency_id: $this->company->currency_id,
            invoice_date: $this->transactionDate->toDateString(),
            due_date: $this->transactionDate->addDays(30)->toDateString(),
            lines: [
                new CreateInvoiceLineDTO(
                    description: 'Test Product in IQD',
                    quantity: 1,
                    unit_price: Money::of(75000, 'IQD'), // 75,000 IQD
                    income_account_id: $this->incomeAccount->id,
                    product_id: $this->product->id,
                    tax_id: null
                ),
            ],
            fiscal_position_id: null
        );

        $invoice = app(\Modules\Sales\Actions\Sales\CreateInvoiceAction::class)->execute($invoiceDto);

        // Act: Post the invoice
        app(InvoiceService::class)->confirm($invoice, $this->user);
        $invoice->refresh();

        // Assert: For base currency, both amounts should be the same
        expect((float) $invoice->exchange_rate_at_creation)->toBe(1.0);
        expect($invoice->total_amount->getAmount()->toFloat())->toBe(75000.0);
        expect($invoice->total_amount_company_currency->getAmount()->toFloat())->toBe(75000.0);

        // Both should be in IQD
        expect($invoice->total_amount->getCurrency()->getCurrencyCode())->toBe('IQD');
        expect($invoice->total_amount_company_currency->getCurrency()->getCurrencyCode())->toBe('IQD');
    });
});
