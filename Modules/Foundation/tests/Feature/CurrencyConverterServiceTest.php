<?php

use Carbon\Carbon;
use Brick\Money\Money;

use App\Models\Company;
use InvalidArgumentException;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\CurrencyRate;
use Modules\Foundation\Services\CurrencyConverterService;

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

    expect(fn() => $service->convertToBaseCurrency($amount, $foreignCurrency, $baseCurrency, Carbon::today(), $company))
        ->toThrow(InvalidArgumentException::class, 'No exchange rate found');
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
    expect($difference->getAmount()->toFloat())->toBe(10.0);
    expect($difference->getCurrency()->getCurrencyCode())->toBe('EUR');
});
