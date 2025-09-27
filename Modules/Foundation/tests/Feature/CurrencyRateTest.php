<?php

use App\Models\Company;
use App\Models\Currency;
use App\Models\CurrencyRate;
use Carbon\Carbon;

test('currency rate can be created', function () {
    $company = Company::factory()->create();
    $currency = \Modules\Foundation\Models\Currency::factory()->create();

    $rate = \Modules\Foundation\Models\CurrencyRate::create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'rate' => 1.2345,
        'effective_date' => Carbon::today(),
        'source' => 'manual',
    ]);

    expect($rate)->toBeInstanceOf(\Modules\Foundation\Models\CurrencyRate::class);
    expect($rate->currency_id)->toBe($currency->id);
    expect($rate->rate)->toBe('1.2345000000');
    expect($rate->effective_date->toDateString())->toBe(Carbon::today()->toDateString());
    expect($rate->source)->toBe('manual');
});

test('currency rate belongs to currency', function () {
    $currency = \Modules\Foundation\Models\Currency::factory()->create();
    $rate = \Modules\Foundation\Models\CurrencyRate::factory()->create(['currency_id' => $currency->id]);

    expect($rate->currency)->toBeInstanceOf(\Modules\Foundation\Models\Currency::class);
    expect($rate->currency->id)->toBe($currency->id);
});

test('currency has many rates', function () {
    $currency = \Modules\Foundation\Models\Currency::factory()->create();
    $rate1 = \Modules\Foundation\Models\CurrencyRate::factory()->create(['currency_id' => $currency->id]);
    $rate2 = \Modules\Foundation\Models\CurrencyRate::factory()->create(['currency_id' => $currency->id]);

    expect($currency->rates)->toHaveCount(2);
    expect($currency->rates->contains($rate1))->toBeTrue();
    expect($currency->rates->contains($rate2))->toBeTrue();
});

test('get rate for date returns correct rate', function () {
    $company = Company::factory()->create();
    $currency = \Modules\Foundation\Models\Currency::factory()->create();

    // Create rates for different dates
    \Modules\Foundation\Models\CurrencyRate::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'rate' => 1.1000,
        'effective_date' => Carbon::today()->subDays(5),
    ]);

    \Modules\Foundation\Models\CurrencyRate::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'rate' => 1.2000,
        'effective_date' => Carbon::today()->subDays(2),
    ]);

    \Modules\Foundation\Models\CurrencyRate::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'rate' => 1.3000,
        'effective_date' => Carbon::today(),
    ]);

    // Should return the most recent rate on or before the date
    $rate = \Modules\Foundation\Models\CurrencyRate::getRateForDate($currency->id, Carbon::today()->subDay(), $company->id);
    expect($rate)->toBe(1.2000);

    $rate = \Modules\Foundation\Models\CurrencyRate::getRateForDate($currency->id, Carbon::today(), $company->id);
    expect($rate)->toBe(1.3000);
});

test('get latest rate returns most recent', function () {
    $company = Company::factory()->create();
    $currency = \Modules\Foundation\Models\Currency::factory()->create();

    \Modules\Foundation\Models\CurrencyRate::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'rate' => 1.1000,
        'effective_date' => Carbon::today()->subDays(5),
    ]);

    \Modules\Foundation\Models\CurrencyRate::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'rate' => 1.3000,
        'effective_date' => Carbon::today(),
    ]);

    $rate = \Modules\Foundation\Models\CurrencyRate::getLatestRate($currency->id, $company->id);
    expect($rate)->toBe(1.3000);
});

test('rate casts to decimal', function () {
    $company = Company::factory()->create();
    $currency = \Modules\Foundation\Models\Currency::factory()->create();
    $rate = \Modules\Foundation\Models\CurrencyRate::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'rate' => 1.234567,
    ]);

    expect($rate->rate)->toBeString();
    expect($rate->rate)->toBe('1.2345670000');
});
