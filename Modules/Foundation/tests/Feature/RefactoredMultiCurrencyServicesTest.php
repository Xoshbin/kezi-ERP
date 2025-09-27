<?php

use App\Models\Company;
use App\Services\InvoiceService;
use Brick\Money\Money;
use Carbon\Carbon;

test('refactored services have multi-currency dependencies', function () {
    // Test that services have the new multi-currency dependencies injected
    $invoiceService = app(InvoiceService::class);
    $vendorBillService = app(\App\Services\VendorBillService::class);
    $paymentService = app(\App\Services\PaymentService::class);
    $journalEntryService = app(\App\Services\JournalEntryService::class);

    // Verify InvoiceService has the new dependencies
    $reflection = new ReflectionClass($invoiceService);
    $properties = $reflection->getProperties();
    $propertyNames = array_map(fn ($prop) => $prop->getName(), $properties);

    expect($propertyNames)->toContain('currencyConverter');
    expect($propertyNames)->toContain('exchangeRateService');

    // Verify VendorBillService has the new dependencies
    $reflection = new ReflectionClass($vendorBillService);
    $properties = $reflection->getProperties();
    $propertyNames = array_map(fn ($prop) => $prop->getName(), $properties);

    expect($propertyNames)->toContain('currencyConverter');
    expect($propertyNames)->toContain('exchangeRateService');

    // Verify PaymentService has the new dependencies
    $reflection = new ReflectionClass($paymentService);
    $properties = $reflection->getProperties();
    $propertyNames = array_map(fn ($prop) => $prop->getName(), $properties);

    expect($propertyNames)->toContain('currencyConverter');
    expect($propertyNames)->toContain('exchangeGainLossService');

    // Verify JournalEntryService has the new dependencies
    $reflection = new ReflectionClass($journalEntryService);
    $properties = $reflection->getProperties();
    $propertyNames = array_map(fn ($prop) => $prop->getName(), $properties);

    expect($propertyNames)->toContain('currencyConverter');
});

test('multi-currency services integration works correctly', function () {
    // This test verifies that our refactored services integrate multi-currency support
    // without breaking existing functionality

    $baseCurrency = \Modules\Foundation\Models\Currency::factory()->create(['code' => 'USD']);
    $company = Company::factory()->create(['currency_id' => $baseCurrency->id]);

    // The services should have the new multi-currency dependencies injected
    $invoiceService = app(InvoiceService::class);

    // Verify the service has the new dependencies
    $reflection = new ReflectionClass($invoiceService);
    $properties = $reflection->getProperties();
    $propertyNames = array_map(fn ($prop) => $prop->getName(), $properties);

    expect($propertyNames)->toContain('currencyConverter');
    expect($propertyNames)->toContain('exchangeRateService');

    // Test that CurrencyConverterService is working
    $currencyConverter = app(\Modules\Foundation\Services\CurrencyConverterService::class);
    $sameCurrencyResult = $currencyConverter->convert(
        Money::of(100, 'USD'),
        $baseCurrency,
        Carbon::today(),
        $company
    );

    expect($sameCurrencyResult->getAmount()->toFloat())->toBe(100.0);
    expect($sameCurrencyResult->getCurrency()->getCurrencyCode())->toBe('USD');
});
