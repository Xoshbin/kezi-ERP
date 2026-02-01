<?php

namespace Jmeryar\Foundation\Tests\Feature\Services;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\CurrencyRate;
use Jmeryar\Foundation\Services\ExchangeRateProviders\FrankfurterProvider;
use Jmeryar\Foundation\Services\ExchangeRateService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->service = app(ExchangeRateService::class);
});

test('it registers default providers on construction', function () {
    // Assert
    $providers = $this->service->getProviders();

    expect($providers)->toHaveKey('frankfurter');
    expect($providers)->toHaveKey('exchangerate-api');
});

test('it can register a custom provider', function () {
    // Arrange
    $customProvider = new FrankfurterProvider;

    // Act
    $this->service->registerProvider($customProvider);

    // Assert
    expect($this->service->getProvider('frankfurter'))->not->toBeNull();
});

test('it returns available providers only', function () {
    // Act
    $availableProviders = $this->service->getAvailableProviders();

    // Assert - Frankfurter should always be available (no API key required)
    expect($availableProviders)->toHaveKey('frankfurter');
});

test('it stores exchange rate for a currency', function () {
    // Arrange
    $company = $this->company;
    $currency = Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);
    $rate = 1.1234;
    $effectiveDate = Carbon::today();

    // Act
    $storedRate = $this->service->storeRate($currency, $rate, $effectiveDate, 'test', $company->id);

    // Assert
    expect($storedRate)->not->toBeNull();
    expect($storedRate->currency_id)->toBe($currency->id);
    expect($storedRate->company_id)->toBe($company->id);
    expect((float) $storedRate->rate)->toEqualWithDelta($rate, 0.0001);
    expect($storedRate->effective_date->toDateString())->toBe($effectiveDate->toDateString());
    expect($storedRate->source)->toBe('test');
});

test('it updates existing rate for same currency and date', function () {
    // Arrange
    $company = $this->company;
    $currency = Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);
    $effectiveDate = Carbon::today();

    // Create initial rate
    $this->service->storeRate($currency, 1.0, $effectiveDate, 'initial', $company->id);

    // Act - Store new rate for same date
    $updatedRate = $this->service->storeRate($currency, 1.5, $effectiveDate, 'updated', $company->id);

    // Assert
    expect(CurrencyRate::where('currency_id', $currency->id)->where('company_id', $company->id)->count())->toBe(1);
    expect((float) $updatedRate->rate)->toEqualWithDelta(1.5, 0.0001);
    expect($updatedRate->source)->toBe('updated');
});

test('it gets latest rate for a currency', function () {
    // Arrange
    $company = $this->company;
    $currency = Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);

    CurrencyRate::factory()->create([
        'currency_id' => $currency->id,
        'company_id' => $company->id,
        'rate' => 1.0,
        'effective_date' => Carbon::today()->subDays(10),
    ]);

    CurrencyRate::factory()->create([
        'currency_id' => $currency->id,
        'company_id' => $company->id,
        'rate' => 1.2,
        'effective_date' => Carbon::today(),
    ]);

    // Act
    $latestRate = $this->service->getLatestRate($currency);

    // Assert
    expect($latestRate)->not->toBeNull();
    expect((float) $latestRate->rate)->toEqualWithDelta(1.2, 0.0001);
});

test('it gets rate for a specific date', function () {
    // Arrange
    $company = $this->company;
    $currency = Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);
    $targetDate = Carbon::today()->subDays(5);

    CurrencyRate::factory()->create([
        'currency_id' => $currency->id,
        'company_id' => $company->id,
        'rate' => 1.0,
        'effective_date' => Carbon::today()->subDays(10),
    ]);

    CurrencyRate::factory()->create([
        'currency_id' => $currency->id,
        'company_id' => $company->id,
        'rate' => 1.1,
        'effective_date' => $targetDate,
    ]);

    CurrencyRate::factory()->create([
        'currency_id' => $currency->id,
        'company_id' => $company->id,
        'rate' => 1.2,
        'effective_date' => Carbon::today(),
    ]);

    // Act
    $rateForDate = $this->service->getRateForDate($currency, $targetDate);

    // Assert
    expect($rateForDate)->not->toBeNull();
    expect((float) $rateForDate->rate)->toEqualWithDelta(1.1, 0.0001);
});

test('it detects significant rate changes', function () {
    // Arrange
    $company = $this->company;
    $currency = Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);

    CurrencyRate::factory()->create([
        'currency_id' => $currency->id,
        'company_id' => $company->id,
        'rate' => 1.0,
        'effective_date' => Carbon::today()->subDay(),
    ]);

    CurrencyRate::factory()->create([
        'currency_id' => $currency->id,
        'company_id' => $company->id,
        'rate' => 1.1, // 10% increase
        'effective_date' => Carbon::today(),
    ]);

    // Act
    $significantChanges = $this->service->detectSignificantRateChanges(5.0);

    // Assert
    expect($significantChanges)->not->toBeEmpty();
    $eurChange = collect($significantChanges)->firstWhere(fn ($c) => $c['currency']->id === $currency->id);
    expect($eurChange)->not->toBeNull();
    expect($eurChange['change_percent'])->toBe(10.0);
});

test('it calculates rate volatility', function () {
    // Arrange
    $company = $this->company;
    $currency = Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);

    // Create rates with some variation
    for ($i = 0; $i < 10; $i++) {
        CurrencyRate::factory()->create([
            'currency_id' => $currency->id,
            'company_id' => $company->id,
            'rate' => 1.0 + ($i * 0.01),
            'effective_date' => Carbon::today()->subDays(10 - $i),
        ]);
    }

    // Act
    $volatility = $this->service->calculateRateVolatility($currency, 30);

    // Assert
    expect($volatility)->not->toBeNull();
    expect($volatility)->toHaveKeys(['min', 'max', 'avg', 'volatility']);
    expect($volatility['min'])->toBeLessThan($volatility['max']);
});
