<?php

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\CurrencyRate;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Services\VendorBillService;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Services\InvoiceService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    // Create foreign currency (USD)
    $this->foreignCurrency = Currency::factory()->create([
        'code' => 'USD',
        'name' => ['en' => 'US Dollar'],
        'symbol' => '$',
        'is_active' => true,
    ]);

    // Create exchange rate for today (future date relative to test document)
    $this->exchangeRate = 1460.0;
    CurrencyRate::create([
        'currency_id' => $this->foreignCurrency->id,
        'company_id' => $this->company->id,
        'rate' => $this->exchangeRate,
        'effective_date' => Carbon::today(), // 2025-09-19
        'source' => 'manual',
    ]);

    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);
    $this->customer = Partner::factory()->customer()->create(['company_id' => $this->company->id]);
    $this->product = Product::factory()->create(['company_id' => $this->company->id]);
});

describe('Exchange Rate Historical Fallback', function () {
    test('vendor bill service falls back to latest rate when no historical rate exists', function () {
        // Create vendor bill with historical date (before our exchange rate)
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->foreignCurrency->id,
            'bill_date' => '2025-05-15', // Before our rate date of 2025-09-19
            'status' => VendorBillStatus::Draft,
            'total_amount' => Money::of(100, 'USD'),
            'total_tax' => Money::of(10, 'USD'),
        ]);

        $service = app(VendorBillService::class);

        // Use reflection to test the protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('processMultiCurrencyAmounts');
        $method->setAccessible(true);

        // This should not throw an exception
        $method->invoke($service, $vendorBill);

        $vendorBill->refresh();

        // Should use the latest available rate (1460.0)
        expect((float) $vendorBill->exchange_rate_at_creation)->toBe($this->exchangeRate);

        // Should convert amounts properly
        expect($vendorBill->total_amount_company_currency->getAmount()->toFloat())->toBe(146000.0); // 100 * 1460
        expect($vendorBill->total_tax_company_currency->getAmount()->toFloat())->toBe(14600.0); // 10 * 1460
    });

    test('invoice service falls back to latest rate when no historical rate exists', function () {
        // Create invoice with historical date (before our exchange rate)
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->foreignCurrency->id,
            'invoice_date' => '2025-05-15', // Before our rate date of 2025-09-19
            'status' => InvoiceStatus::Draft,
            'total_amount' => Money::of(200, 'USD'),
            'total_tax' => Money::of(20, 'USD'),
        ]);

        $service = app(InvoiceService::class);

        // Use reflection to test the protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('processMultiCurrencyAmounts');
        $method->setAccessible(true);

        // This should not throw an exception
        $method->invoke($service, $invoice);

        $invoice->refresh();

        // Should use the latest available rate (1460.0)
        expect((float) $invoice->exchange_rate_at_creation)->toBe($this->exchangeRate);

        // Should convert amounts properly
        expect($invoice->total_amount_company_currency->getAmount()->toFloat())->toBe(292000.0); // 200 * 1460
        expect($invoice->total_tax_company_currency->getAmount()->toFloat())->toBe(29200.0); // 20 * 1460
    });

    test('vendor bill service uses manual exchange rate when provided', function () {
        $manualRate = 1500.0;

        // Create vendor bill with manual exchange rate
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->foreignCurrency->id,
            'bill_date' => '2025-05-15',
            'status' => VendorBillStatus::Draft,
            'total_amount' => Money::of(100, 'USD'),
            'total_tax' => Money::of(10, 'USD'),
            'exchange_rate_at_creation' => $manualRate,
        ]);

        $service = app(VendorBillService::class);

        // Use reflection to test the protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('processMultiCurrencyAmounts');
        $method->setAccessible(true);

        $method->invoke($service, $vendorBill);

        $vendorBill->refresh();

        // Should use the manual rate, not the latest rate
        expect((float) $vendorBill->exchange_rate_at_creation)->toBe($manualRate);

        // Should convert amounts using manual rate
        expect($vendorBill->total_amount_company_currency->getAmount()->toFloat())->toBe(150000.0); // 100 * 1500
        expect($vendorBill->total_tax_company_currency->getAmount()->toFloat())->toBe(15000.0); // 10 * 1500
    });

    test('services handle base currency documents correctly', function () {
        // Create vendor bill in base currency (IQD)
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id, // Base currency
            'bill_date' => '2025-05-15',
            'status' => VendorBillStatus::Draft,
            'total_amount' => Money::of(100000, 'IQD'),
            'total_tax' => Money::of(10000, 'IQD'),
        ]);

        $service = app(VendorBillService::class);

        // Use reflection to test the protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('processMultiCurrencyAmounts');
        $method->setAccessible(true);

        $method->invoke($service, $vendorBill);

        $vendorBill->refresh();

        // Should use rate of 1.0 for base currency
        expect((float) $vendorBill->exchange_rate_at_creation)->toBe(1.0);

        // Amounts should remain the same
        expect($vendorBill->total_amount_company_currency->getAmount()->toFloat())->toBe(100000.0);
        expect($vendorBill->total_tax_company_currency->getAmount()->toFloat())->toBe(10000.0);
    });
});
