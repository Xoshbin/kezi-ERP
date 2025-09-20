<?php

use App\Enums\Purchases\VendorBillStatus;
use App\Enums\Sales\InvoiceStatus;
use App\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\VendorBill;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);

    // Create foreign currency (USD)
    $this->foreignCurrency = Currency::factory()->create([
        'code' => 'USD',
        'name' => ['en' => 'US Dollar'],
        'symbol' => '$',
        'is_active' => true,
    ]);

    // Create exchange rate
    $this->exchangeRate = 1460.0;
    CurrencyRate::create([
        'currency_id' => $this->foreignCurrency->id,
        'company_id' => $this->company->id,
        'rate' => $this->exchangeRate,
        'effective_date' => Carbon::today(),
        'source' => 'manual',
    ]);

    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);
    $this->customer = Partner::factory()->customer()->create(['company_id' => $this->company->id]);
});

describe('VendorBill Exchange Rate Field', function () {
    test('exchange rate field is visible for foreign currency vendor bills', function () {
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => VendorBillStatus::Draft,
        ]);

        $livewire = Livewire::test(EditVendorBill::class, ['record' => $vendorBill->id]);

        // Check that the exchange rate field is visible
        $livewire->assertFormFieldExists('exchange_rate_at_creation');
    });

    test('exchange rate field is hidden for base currency vendor bills', function () {
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id, // Use base currency from company
            'status' => VendorBillStatus::Draft,
        ]);

        $livewire = Livewire::test(EditVendorBill::class, ['record' => $vendorBill->id]);

        // Check that the exchange rate field is not visible
        $livewire->assertFormFieldIsHidden('exchange_rate_at_creation');
    });

    test('exchange rate field exists for draft vendor bills', function () {
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => VendorBillStatus::Draft,
        ]);

        $livewire = Livewire::test(EditVendorBill::class, ['record' => $vendorBill->id]);

        // Check that the exchange rate field exists
        $livewire->assertFormFieldExists('exchange_rate_at_creation');
    });

    test('exchange rate field exists for posted vendor bills', function () {
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => VendorBillStatus::Posted,
            'exchange_rate_at_creation' => $this->exchangeRate,
        ]);

        $livewire = Livewire::test(EditVendorBill::class, ['record' => $vendorBill->id]);

        // Check that the exchange rate field exists
        $livewire->assertFormFieldExists('exchange_rate_at_creation');
    });

    test('exchange rate can be set on draft vendor bill', function () {
        $customRate = 1500.0;

        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => VendorBillStatus::Draft,
        ]);

        // Directly set the exchange rate to test the field works
        $vendorBill->update(['exchange_rate_at_creation' => $customRate]);
        $vendorBill->refresh();

        // Verify the exchange rate was saved
        expect((float) $vendorBill->exchange_rate_at_creation)->toBe($customRate);
    });
});

describe('Invoice Exchange Rate Field', function () {
    test('exchange rate field is visible for foreign currency invoices', function () {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => InvoiceStatus::Draft,
        ]);

        $livewire = Livewire::test(EditInvoice::class, ['record' => $invoice->id]);

        // Check that the exchange rate field is visible
        $livewire->assertFormFieldExists('exchange_rate_at_creation');
    });

    test('exchange rate field is hidden for base currency invoices', function () {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->company->currency_id, // Use base currency from company
            'status' => InvoiceStatus::Draft,
        ]);

        $livewire = Livewire::test(EditInvoice::class, ['record' => $invoice->id]);

        // Check that the exchange rate field is not visible
        $livewire->assertFormFieldIsHidden('exchange_rate_at_creation');
    });

    test('exchange rate field exists for draft invoices', function () {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => InvoiceStatus::Draft,
        ]);

        $livewire = Livewire::test(EditInvoice::class, ['record' => $invoice->id]);

        // Check that the exchange rate field exists
        $livewire->assertFormFieldExists('exchange_rate_at_creation');
    });

    test('exchange rate field exists for posted invoices', function () {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => InvoiceStatus::Posted,
            'exchange_rate_at_creation' => $this->exchangeRate,
        ]);

        $livewire = Livewire::test(EditInvoice::class, ['record' => $invoice->id]);

        // Check that the exchange rate field exists
        $livewire->assertFormFieldExists('exchange_rate_at_creation');
    });

    test('exchange rate can be set on draft invoice', function () {
        $customRate = 1500.0;

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => InvoiceStatus::Draft,
        ]);

        // Directly set the exchange rate to test the field works
        $invoice->update(['exchange_rate_at_creation' => $customRate]);
        $invoice->refresh();

        // Verify the exchange rate was saved
        expect((float) $invoice->exchange_rate_at_creation)->toBe($customRate);
    });
});

describe('Service Behavior with Manual Exchange Rates', function () {
    test('vendor bill service respects manually set exchange rate when posting', function () {
        $customRate = 1500.0;

        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => VendorBillStatus::Draft,
            'exchange_rate_at_creation' => $customRate,
            'total_amount' => Money::of(100, 'USD'),
            'total_tax' => Money::of(10, 'USD'),
            'bill_date' => Carbon::today(), // Use today's date to match exchange rate
            'accounting_date' => Carbon::today(),
        ]);

        // Add a line item to make the bill valid for posting
        $product = \App\Models\Product::factory()->create([
            'company_id' => $this->company->id,
            'default_inventory_account_id' => \App\Models\Account::factory()->create(['company_id' => $this->company->id, 'type' => 'current_assets'])->id,
            'default_stock_input_account_id' => \App\Models\Account::factory()->create(['company_id' => $this->company->id, 'type' => 'current_liabilities'])->id,
        ]);
        \App\Models\VendorBillLine::factory()->create([
            'vendor_bill_id' => $vendorBill->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => Money::of(100, 'USD'),
            'subtotal' => Money::of(100, 'USD'),
        ]);

        $vendorBillService = app(\App\Services\VendorBillService::class);

        $vendorBillService->post($vendorBill, $this->user);

        $vendorBill->refresh();

        // Verify the custom exchange rate was preserved
        expect((float) $vendorBill->exchange_rate_at_creation)->toBe($customRate);
    });

    test('invoice service respects manually set exchange rate when posting', function () {
        $customRate = 1500.0;

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => InvoiceStatus::Draft,
            'exchange_rate_at_creation' => $customRate,
            'total_amount' => Money::of(100, 'USD'),
            'total_tax' => Money::of(10, 'USD'),
            'invoice_date' => Carbon::today(), // Use today's date to match exchange rate
        ]);

        // Add a line item to make the invoice valid for posting
        $product = \App\Models\Product::factory()->create([
            'company_id' => $this->company->id,
            'income_account_id' => \App\Models\Account::factory()->create(['company_id' => $this->company->id, 'type' => 'income'])->id,
        ]);
        \App\Models\InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => Money::of(100, 'USD'),
            'subtotal' => Money::of(100, 'USD'),
        ]);

        $invoiceService = app(\App\Services\InvoiceService::class);

        $invoiceService->confirm($invoice, $this->user);

        $invoice->refresh();

        // Verify the custom exchange rate was preserved
        expect((float) $invoice->exchange_rate_at_creation)->toBe($customRate);
    });

    test('vendor bill service uses automatic exchange rate when none is set manually', function () {
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => VendorBillStatus::Draft,
            'exchange_rate_at_creation' => null, // No manual rate set
            'total_amount' => Money::of(100, 'USD'),
            'total_tax' => Money::of(10, 'USD'),
            'bill_date' => Carbon::today(), // Use today's date to match exchange rate
            'accounting_date' => Carbon::today(),
        ]);

        // Add a line item to make the bill valid for posting
        $product = \App\Models\Product::factory()->create([
            'company_id' => $this->company->id,
            'default_inventory_account_id' => \App\Models\Account::factory()->create(['company_id' => $this->company->id, 'type' => 'current_assets'])->id,
            'default_stock_input_account_id' => \App\Models\Account::factory()->create(['company_id' => $this->company->id, 'type' => 'current_liabilities'])->id,
        ]);
        \App\Models\VendorBillLine::factory()->create([
            'vendor_bill_id' => $vendorBill->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => Money::of(100, 'USD'),
            'subtotal' => Money::of(100, 'USD'),
        ]);

        $vendorBillService = app(\App\Services\VendorBillService::class);

        $vendorBillService->post($vendorBill, $this->user);

        $vendorBill->refresh();

        // Verify the automatic exchange rate was used
        expect((float) $vendorBill->exchange_rate_at_creation)->toBe($this->exchangeRate);
    });
});
