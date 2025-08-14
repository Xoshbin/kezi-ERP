<?php

namespace Tests\Feature\Services;

use App\Models\Company;
use App\Models\Currency;
use App\Services\CurrencyConverterService;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new CurrencyConverterService();

    // Create test currencies
    $this->iqd = Currency::create([
        'code' => 'IQD',
        'name' => 'Iraqi Dinar',
        'symbol' => 'IQD',
        'exchange_rate' => 1.0, // Base currency
        'is_active' => true,
        'decimal_places' => 3,
    ]);

    $this->usd = Currency::create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'exchange_rate' => 1460.0, // 1 USD = 1460 IQD
        'is_active' => true,
        'decimal_places' => 2,
    ]);

    $this->eur = Currency::create([
        'code' => 'EUR',
        'name' => 'Euro',
        'symbol' => '€',
        'exchange_rate' => 1600.0, // 1 EUR = 1600 IQD
        'is_active' => true,
        'decimal_places' => 2,
    ]);

    $this->company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
    ]);
});

test('getExchangeRate returns 1.0 for same currency', function () {
    $rate = $this->service->getExchangeRate($this->iqd, $this->iqd);
    expect($rate)->toBe(1.0);
});

test('getExchangeRate returns correct rate for different currencies', function () {
    $rate = $this->service->getExchangeRate($this->usd, $this->iqd);
    expect($rate)->toBe(1460.0);

    $rate = $this->service->getExchangeRate($this->eur, $this->iqd);
    expect($rate)->toBe(1600.0);
});

test('convertAmount converts USD to IQD correctly', function () {
    $usdAmount = Money::of(100, 'USD');
    $convertedAmount = $this->service->convertAmount($usdAmount, $this->iqd);

    expect($convertedAmount->getCurrency()->getCurrencyCode())->toBe('IQD');
    expect($convertedAmount->isEqualTo(Money::of(146000, 'IQD')))->toBeTrue();
});

test('convertAmount with same currency returns original amount', function () {
    $iqdAmount = Money::of(100000, 'IQD');
    $convertedAmount = $this->service->convertAmount($iqdAmount, $this->iqd);

    expect($convertedAmount)->toBe($iqdAmount);
});

test('convertAmount with custom exchange rate', function () {
    $usdAmount = Money::of(100, 'USD');
    $customRate = 1500.0;
    $convertedAmount = $this->service->convertAmount($usdAmount, $this->iqd, $customRate);

    expect($convertedAmount->isEqualTo(Money::of(150000, 'IQD')))->toBeTrue();
});

test('convertToCompanyBaseCurrency returns complete conversion result', function () {
    $usdAmount = Money::of(250, 'USD');
    $result = $this->service->convertToCompanyBaseCurrency($usdAmount, $this->usd, $this->company);

    expect($result->originalAmount)->toBe($usdAmount);
    expect($result->originalCurrency->id)->toBe($this->usd->id);
    expect($result->targetCurrency->id)->toBe($this->iqd->id);
    expect($result->exchangeRate)->toBe(1460.0);
    expect($result->convertedAmount->isEqualTo(Money::of(365000, 'IQD')))->toBeTrue();
    expect($result->wasConverted())->toBeTrue();
});

test('convertToCompanyBaseCurrency with same currency', function () {
    $iqdAmount = Money::of(100000, 'IQD');
    $result = $this->service->convertToCompanyBaseCurrency($iqdAmount, $this->iqd, $this->company);

    expect($result->originalAmount)->toBe($iqdAmount);
    expect($result->convertedAmount)->toBe($iqdAmount);
    expect($result->exchangeRate)->toBe(1.0);
    expect($result->wasConverted())->toBeFalse();
});

test('convertMultipleToCompanyBaseCurrency handles multiple amounts', function () {
    $amounts = [
        'debit' => Money::of(100, 'USD'),
        'credit' => Money::of(50, 'USD'),
    ];

    $results = $this->service->convertMultipleToCompanyBaseCurrency($amounts, $this->usd, $this->company);

    expect($results)->toHaveCount(2);
    expect($results['debit']->convertedAmount->isEqualTo(Money::of(146000, 'IQD')))->toBeTrue();
    expect($results['credit']->convertedAmount->isEqualTo(Money::of(73000, 'IQD')))->toBeTrue();
});

test('isSameCurrency correctly identifies same currencies', function () {
    expect($this->service->isSameCurrency($this->iqd, $this->iqd))->toBeTrue();
    expect($this->service->isSameCurrency($this->usd, $this->iqd))->toBeFalse();
});

test('needsConversionToBaseCurrency correctly identifies conversion needs', function () {
    expect($this->service->needsConversionToBaseCurrency($this->usd, $this->company))->toBeTrue();
    expect($this->service->needsConversionToBaseCurrency($this->iqd, $this->company))->toBeFalse();
});

test('createZeroAmount creates zero in correct currency', function () {
    $zero = $this->service->createZeroAmount($this->usd);
    expect($zero->isZero())->toBeTrue();
    expect($zero->getCurrency()->getCurrencyCode())->toBe('USD');
});

test('validateCurrenciesForConversion passes for valid currencies', function () {
    // This should not throw any exception
    $this->service->validateCurrenciesForConversion($this->usd, $this->iqd);
    expect(true)->toBeTrue(); // If we get here, no exception was thrown
});

test('validateCurrenciesForConversion throws for inactive source currency', function () {
    $this->usd->update(['is_active' => false]);

    expect(fn() => $this->service->validateCurrenciesForConversion($this->usd, $this->iqd))
        ->toThrow(\InvalidArgumentException::class, 'Source currency USD is not active');
});

test('validateCurrenciesForConversion throws for inactive target currency', function () {
    $this->iqd->update(['is_active' => false]);

    expect(fn() => $this->service->validateCurrenciesForConversion($this->usd, $this->iqd))
        ->toThrow(\InvalidArgumentException::class, 'Target currency IQD is not active');
});

test('validateCurrenciesForConversion throws for invalid exchange rate', function () {
    $this->usd->update(['exchange_rate' => 0]);

    expect(fn() => $this->service->validateCurrenciesForConversion($this->usd, $this->iqd))
        ->toThrow(\InvalidArgumentException::class, 'Source currency USD has invalid exchange rate');
});

test('conversion result DTO provides useful methods', function () {
    $usdAmount = Money::of(100, 'USD');
    $result = $this->service->convertToCompanyBaseCurrency($usdAmount, $this->usd, $this->company);

    // Test DTO methods
    expect($result->wasConverted())->toBeTrue();
    $summary = $result->getSummary();
    expect($summary)->toContain('Converted');
    expect($summary)->toContain('100.00'); // Amount should be in summary
    expect($summary)->toContain('146,000'); // Converted amount should be in summary

    $zeroInTarget = $result->createZeroInTargetCurrency();
    expect($zeroInTarget->isZero())->toBeTrue();
    expect($zeroInTarget->getCurrency()->getCurrencyCode())->toBe('IQD');
});

test('conversion result DTO handles same currency correctly', function () {
    $iqdAmount = Money::of(100000, 'IQD');
    $result = $this->service->convertToCompanyBaseCurrency($iqdAmount, $this->iqd, $this->company);

    expect($result->wasConverted())->toBeFalse();
    expect($result->getSummary())->toContain('No conversion needed');
    expect($result->getSummary())->toContain('same currency');
});
