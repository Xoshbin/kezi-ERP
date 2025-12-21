<?php

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\CurrencyRate;

test('can convert between same currency', function () {
    $company = Company::factory()->create();
    $currency = Currency::factory()->create(['code' => 'USD']);

    $amount = Money::of(100, 'USD');
    $service = app(\Modules\Foundation\Services\CurrencyConverterService::class);

    $converted = $service->convert($amount, $currency, Carbon::today(), $company);

    expect($converted->getAmount()->toFloat())->toBe(100.0);
    expect($converted->getCurrency()->getCurrencyCode())->toBe('USD');
});

test('can convert to base currency', function () {
    $baseCurrency = Currency::factory()->create(['code' => 'USD']);
    $company = Company::factory()->create(['currency_id' => $baseCurrency->id]);
    $foreignCurrency = Currency::factory()->create(['code' => 'EUR']);

    // Create exchange rate: 1 EUR = 1.5 USD
    CurrencyRate::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $foreignCurrency->id,
        'rate' => 1.5,
        'effective_date' => Carbon::today(),
    ]);

    $amount = Money::of(100, 'EUR');
    $service = app(\Modules\Foundation\Services\CurrencyConverterService::class);

    $converted = $service->convertToBaseCurrency($amount, $foreignCurrency, $baseCurrency, Carbon::today(), $company);

    expect($converted->getAmount()->toFloat())->toBe(150.0);
    expect($converted->getCurrency()->getCurrencyCode())->toBe('USD');
});

test('can convert from base currency', function () {
    $baseCurrency = Currency::factory()->create(['code' => 'USD']);
    $company = Company::factory()->create(['currency_id' => $baseCurrency->id]);
    $foreignCurrency = Currency::factory()->create(['code' => 'EUR']);

    // Create exchange rate: 1 EUR = 1.5 USD
    CurrencyRate::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $foreignCurrency->id,
        'rate' => 1.5,
        'effective_date' => Carbon::today(),
    ]);

    $amount = Money::of(150, 'USD');
    $service = app(\Modules\Foundation\Services\CurrencyConverterService::class);

    $converted = $service->convertFromBaseCurrency($amount, $foreignCurrency, Carbon::today(), $company);

    expect($converted->getAmount()->toFloat())->toBe(100.0);
    expect($converted->getCurrency()->getCurrencyCode())->toBe('EUR');
});

test('can convert between foreign currencies via base currency', function () {
    $baseCurrency = Currency::factory()->create(['code' => 'USD']);
    $company = Company::factory()->create(['currency_id' => $baseCurrency->id]);
    $currency1 = Currency::factory()->create(['code' => 'EUR']);
    $currency2 = Currency::factory()->create(['code' => 'GBP']);

    // Create exchange rates
    // 1 EUR = 1.5 USD
    CurrencyRate::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency1->id,
        'rate' => 1.5,
        'effective_date' => Carbon::today(),
    ]);

    // 1 GBP = 2.0 USD
    CurrencyRate::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency2->id,
        'rate' => 2.0,
        'effective_date' => Carbon::today(),
    ]);

    $amount = Money::of(100, 'EUR');
    $service = app(\Modules\Foundation\Services\CurrencyConverterService::class);

    $converted = $service->convert($amount, $currency2, Carbon::today(), $company);

    // 100 EUR = 150 USD = 75 GBP
    expect($converted->getAmount()->toFloat())->toBe(75.0);
    expect($converted->getCurrency()->getCurrencyCode())->toBe('GBP');
});

test('throws exception when no exchange rate found', function () {
    $baseCurrency = Currency::factory()->create(['code' => 'USD']);
    $company = Company::factory()->create(['currency_id' => $baseCurrency->id]);
    $foreignCurrency = Currency::factory()->create(['code' => 'EUR']);

    $amount = Money::of(100, 'EUR');
    $service = app(\Modules\Foundation\Services\CurrencyConverterService::class);

    expect(fn () => $service->convertToBaseCurrency($amount, $foreignCurrency, $baseCurrency, Carbon::today(), $company))
        ->toThrow(\InvalidArgumentException::class, 'No exchange rate found');
});

test('can convert with specific rate', function () {
    $service = app(\Modules\Foundation\Services\CurrencyConverterService::class);
    $amount = Money::of(100, 'USD');

    // Convert from foreign to base (multiply by rate)
    $converted = $service->convertWithRate($amount, 1.5, 'EUR', false);
    expect($converted->getAmount()->toFloat())->toBe(150.0);
    expect($converted->getCurrency()->getCurrencyCode())->toBe('EUR');

    // Convert from base to foreign (divide by rate)
    $converted = $service->convertWithRate($amount, 2.0, 'EUR', true);
    expect($converted->getAmount()->toFloat())->toBe(50.0);
    expect($converted->getCurrency()->getCurrencyCode())->toBe('EUR');
});

test('can calculate exchange difference', function () {
    $service = app(\Modules\Foundation\Services\CurrencyConverterService::class);

    $originalAmount = Money::of(100, 'USD');
    $currentAmount = Money::of(150, 'EUR');

    $difference = $service->calculateExchangeDifference(
        $originalAmount,
        $currentAmount,
        1.5, // original rate
        1.6, // current rate
        'EUR'
    );

    // 100 USD at 1.6 rate = 160 EUR, difference = 160 - 150 = 10 EUR
    expect($difference->getCurrency()->getCurrencyCode())->toBe('EUR');
});

test('convertWithRate correctly handles 1000 USD to IQD at 1460', function () {
    $service = app(\Modules\Foundation\Services\CurrencyConverterService::class);
    // 1000 USD (Major units)
    $amount = Money::of(1000, 'USD');
    $rate = 1460.0;

    // Convert to IQD (Base)
    $converted = $service->convertWithRate($amount, $rate, 'IQD', false);

    // 1000 * 1460 = 1,460,000
    // We expect 1.46 Million
    expect($converted->getAmount()->toFloat())->toBe(1460000.0);
    expect($converted->getCurrency()->getCurrencyCode())->toBe('IQD');
});

test('reproduces 1000 USD to IQD conversion bug', function () {
    // Setup IQD as base currency with 3 decimal places
    $baseCurrency = Currency::factory()->create(['code' => 'IQD', 'decimal_places' => 3]);
    $company = Company::factory()->create(['currency_id' => $baseCurrency->id]);

    // Setup USD as foreign currency with 2 decimal places
    $usdCurrency = Currency::factory()->create(['code' => 'USD', 'decimal_places' => 2]);

    // Rate: 1 USD = 1460 IQD
    CurrencyRate::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $usdCurrency->id,
        'rate' => 1460, // 1 USD = 1460 IQD
        'effective_date' => Carbon::today(),
    ]);

    // 1000 USD
    $amount = Money::of(1000, 'USD');
    $service = app(\Modules\Foundation\Services\CurrencyConverterService::class);

    // Convert to Base (IQD)
    $converted = $service->convertToBaseCurrency($amount, $usdCurrency, $baseCurrency, Carbon::today(), $company);

    // Expect 1,460,000 IQD
    // If bug is present, this might return 14,600,000,000 or similar
    expect($converted->getAmount()->toFloat())->toBe(1460000.0);
    expect($converted->getCurrency()->getCurrencyCode())->toBe('IQD');
});
