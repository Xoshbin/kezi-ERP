<?php

use Carbon\Carbon;

use App\Models\Company;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\CurrencyRate;

test('currency rate can be created', function () {
    $company = Company::factory()->create();
    $currency = Currency::factory()->create();

    $rate = CurrencyRate::create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'rate' => 1.2345,
        'effective_date' => Carbon::today(),
        'source' => 'manual',
    ]);

    expect($rate)->toBeInstanceOf(CurrencyRate::class);
    expect($rate->currency_id)->toBe($currency->id);
    expect($rate->rate)->toBe('1.2345000000');
    expect($rate->effective_date->toDateString())->toBe(Carbon::today()->toDateString());
    expect($rate->source)->toBe('manual');
});

test('currency rate belongs to currency', function () {
    $currency = Currency::factory()->create();
    $rate = CurrencyRate::factory()->create(['currency_id' => $currency->id]);

    expect($rate->currency)->toBeInstanceOf(Currency::class);
    expect($rate->currency->id)->toBe($currency->id);
});

test('currency has many rates', function () {
    $currency = Currency::factory()->create();
    $rate1 = CurrencyRate::factory()->create(['currency_id' => $currency->id]);
    $rate2 = CurrencyRate::factory()->create(['currency_id' => $currency->id]);

    expect($currency->rates)->toHaveCount(2);
    expect($currency->rates->contains($rate1))->toBeTrue();
    expect($currency->rates->contains($rate2))->toBeTrue();
});

test('get rate for date returns correct rate', function () {
    $company = Company::factory()->create();
    $currency = Currency::factory()->create();

    // Create rates for different dates
    CurrencyRate::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'rate' => 1.1000,
        'effective_date' => Carbon::today()->subDays(5),
    ]);

    CurrencyRate::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'rate' => 1.2000,
        'effective_date' => Carbon::today()->subDays(2),
    ]);

    CurrencyRate::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'rate' => 1.3000,
        'effective_date' => Carbon::today(),
    ]);

    // Should return the most recent rate on or before the date
    $rate = CurrencyRate::getRateForDate($currency->id, Carbon::today()->subDay(), $company->id);
    expect($rate)->toBe(1.2000);

    $rate = CurrencyRate::getRateForDate($currency->id, Carbon::today(), $company->id);
    expect($rate)->toBe(1.3000);
});

test('get latest rate returns most recent', function () {
    $company = Company::factory()->create();
    $currency = Currency::factory()->create();

    CurrencyRate::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'rate' => 1.1000,
        'effective_date' => Carbon::today()->subDays(5),
    ]);

    CurrencyRate::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'rate' => 1.3000,
        'effective_date' => Carbon::today(),
    ]);

    $rate = CurrencyRate::getLatestRate($currency->id, $company->id);
    expect($rate)->toBe(1.3000);
});

test('rate casts to decimal', function () {
    $company = Company::factory()->create();
    $currency = Currency::factory()->create();
    $rate = CurrencyRate::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'rate' => 1.234567,
    ]);

    expect($rate->rate)->toBeString();
    expect($rate->rate)->toBe('1.2345670000');
});
