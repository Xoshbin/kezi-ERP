<?php

use Carbon\Carbon;

use Brick\Money\Money;
use App\Models\Company;
use Modules\Foundation\Models\Currency;
use Modules\Sales\Services\InvoiceService;
use Modules\Payment\Services\PaymentService;
use Modules\Purchase\Services\VendorBillService;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Foundation\Services\CurrencyConverterService;

test('refactored services have multi-currency dependencies', function () {
    // Test that services have the new multi-currency dependencies injected
    $invoiceService = app(InvoiceService::class);
    $vendorBillService = app(VendorBillService::class);
    $paymentService = app(PaymentService::class);
    $journalEntryService = app(JournalEntryService::class);

    // Verify InvoiceService has the new dependencies
    $reflection = new \ReflectionClass($invoiceService);
    $properties = $reflection->getProperties();
    $propertyNames = array_map(fn($prop) => $prop->getName(), $properties);

    expect($propertyNames)->toContain('currencyConverter');
    expect($propertyNames)->toContain('exchangeRateService');

    // Verify VendorBillService has the new dependencies
    $reflection = new \ReflectionClass($vendorBillService);
    $properties = $reflection->getProperties();
    $propertyNames = array_map(fn($prop) => $prop->getName(), $properties);

    expect($propertyNames)->toContain('currencyConverter');
    expect($propertyNames)->toContain('exchangeRateService');

    // Verify PaymentService has the new dependencies
    $reflection = new \ReflectionClass($paymentService);
    $properties = $reflection->getProperties();
    $propertyNames = array_map(fn($prop) => $prop->getName(), $properties);

    expect($propertyNames)->toContain('currencyConverter');
    expect($propertyNames)->toContain('exchangeGainLossService');

    // Verify JournalEntryService has the new dependencies
    $reflection = new \ReflectionClass($journalEntryService);
    $properties = $reflection->getProperties();
    $propertyNames = array_map(fn($prop) => $prop->getName(), $properties);

    expect($propertyNames)->toContain('currencyConverter');
});

test('multi-currency services integration works correctly', function () {
    // This test verifies that our refactored services integrate multi-currency support
    // without breaking existing functionality

    $baseCurrency = Currency::factory()->create(['code' => 'USD']);
    $company = Company::factory()->create(['currency_id' => $baseCurrency->id]);

    // The services should have the new multi-currency dependencies injected
    $invoiceService = app(InvoiceService::class);

    // Verify the service has the new dependencies
    $reflection = new \ReflectionClass($invoiceService);
    $properties = $reflection->getProperties();
    $propertyNames = array_map(fn($prop) => $prop->getName(), $properties);

    expect($propertyNames)->toContain('currencyConverter');
    expect($propertyNames)->toContain('exchangeRateService');

    // Test that CurrencyConverterService is working
    $currencyConverter = app(CurrencyConverterService::class);
    $sameCurrencyResult = $currencyConverter->convert(
        Money::of(100, 'USD'),
        $baseCurrency,
        Carbon::today(),
        $company
    );

    expect($sameCurrencyResult->getAmount()->toFloat())->toBe(100.0);
    expect($sameCurrencyResult->getCurrency()->getCurrencyCode())->toBe('USD');
});
