<?php

namespace Kezi\Accounting\Tests\Feature\Services\Consolidation;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Enums\Consolidation\CurrencyTranslationMethod;
use Kezi\Accounting\Services\Consolidation\CurrencyTranslationService;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\CurrencyRate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->baseCurrency = Currency::factory()->create(['code' => 'USD']);
    $this->foreignCurrency = Currency::factory()->create(['code' => 'EUR']);

    $this->company = Company::factory()->create(['currency_id' => $this->baseCurrency->id]);

    $this->service = app(CurrencyTranslationService::class);
});

test('it translates using closing rate (spot rate at date)', function () {
    // Setup Rate: 1 EUR = 1.1 USD on 2026-01-01
    CurrencyRate::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->foreignCurrency->id,
        'effective_date' => '2026-01-01',
        'rate' => 1.10,
    ]);

    $amount = Money::of(100, 'EUR');
    $date = Carbon::parse('2026-01-01');

    $result = $this->service->translate(
        $amount,
        $this->baseCurrency,
        $date,
        CurrencyTranslationMethod::ClosingRate,
        $this->company
    );

    expect($result->getAmount()->toFloat())->toEqual(110.00); // 100 * 1.1
    expect($result->getCurrency()->getCurrencyCode())->toBe('USD');
});

test('it translates using average rate over period', function () {
    // Setup Rates for Jan 2026
    // Jan 1: 1.0
    // Jan 31: 1.2
    // Average should be 1.1 (if simple average of these two records is taken by query)

    CurrencyRate::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->foreignCurrency->id,
        'effective_date' => '2026-01-01',
        'rate' => 1.00,
    ]);

    CurrencyRate::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->foreignCurrency->id,
        'effective_date' => '2026-01-31',
        'rate' => 1.20,
    ]);

    $amount = Money::of(100, 'EUR');
    $date = Carbon::parse('2026-01-31'); // Reporting date
    $period = [
        'start' => Carbon::parse('2026-01-01'),
        'end' => Carbon::parse('2026-01-31'),
    ];

    $result = $this->service->translate(
        $amount,
        $this->baseCurrency,
        $date,
        CurrencyTranslationMethod::AverageRate,
        $this->company,
        $period
    );

    // Average rate = (1.0 + 1.2) / 2 = 1.1
    // 100 * 1.1 = 110
    expect($result->getAmount()->toFloat())->toEqual(110.00);
});

test('it throws exception if period missing for average rate', function () {
    $amount = Money::of(100, 'EUR');

    $this->service->translate(
        $amount,
        $this->baseCurrency, // corrected argument order
        now(),
        CurrencyTranslationMethod::AverageRate,
        $this->company
    );
})->throws(\InvalidArgumentException::class);
