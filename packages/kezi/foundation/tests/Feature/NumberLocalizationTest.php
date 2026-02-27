<?php

use Illuminate\Support\Number;
use Kezi\Foundation\Support\NumberFormatter;

test('global number formatting respects configured locale independently of app locale', function () {
    // Ensure we start with English formatting
    config(['formatting.number_locale' => 'en']);
    Number::useLocale('en');

    $number = 1234.56;

    // Check with Kurdish locale
    app()->setLocale('ckb');
    expect(app()->getLocale())->toBe('ckb');

    // Standard format should be English numerals
    expect(Number::format($number))->toBe('1,234.56');

    // Kurdish numerals for comparison (what we DON'T want in English mode)
    // ١,٢٣٤.٥٦ is the Kurdish representation
    expect(Number::format($number))->not->toBe('١٬٢٣٤٫٥٦');

    // Check with Arabic locale
    app()->setLocale('ar');
    expect(Number::format($number))->toBe('1,234.56');
});

test('global money formatting respects configured locale independently of app locale', function () {
    config(['formatting.currency_locale' => 'en']);
    Number::useLocale('en');

    $amount = 1234.56;

    app()->setLocale('ckb');

    // Should use English numerals even in Kurdish locale
    expect(Number::currency($amount, 'USD'))->toContain('1,234.56');
});

test('filament components respect global number locale via schema', function () {
    config(['formatting.number_locale' => 'en']);
    $numberLocale = NumberFormatter::getNumberLocale();

    $schema = \Filament\Schemas\Schema::make();

    expect($schema->getDefaultNumberLocale())->toBe($numberLocale);
});
