<?php

use App\Support\NumberFormatter;
use Brick\Money\Money;

it('formats money with English locale by default', function () {
    $money = Money::of(1234.56, 'USD');
    
    $formatted = NumberFormatter::formatMoney($money);
    
    // Should use English numerals regardless of app locale
    expect($formatted)->toContain('1,234.56');
});

it('formats money using formatMoneyTo with configured locale', function () {
    $money = Money::of(1234.56, 'USD');
    
    $formatted = NumberFormatter::formatMoneyTo($money);
    
    // Should use English numerals regardless of app locale
    expect($formatted)->toContain('1,234.56');
});

it('formats numbers with English locale by default', function () {
    $formatted = NumberFormatter::formatNumber(1234.56);
    
    // Should use English numerals
    expect($formatted)->toBe('1,234.56');
});

it('formats percentages with English locale by default', function () {
    $formatted = NumberFormatter::formatPercentage(25.5);
    
    // Should use English numerals
    expect($formatted)->toBe('25.5%');
});

it('respects auto locale setting', function () {
    // Temporarily change config to auto
    config(['formatting.number_locale' => 'auto']);
    
    // Set app locale to Kurdish
    app()->setLocale('ckb');
    
    $locale = NumberFormatter::getNumberLocale();
    
    expect($locale)->toBe('ckb');
    
    // Reset config
    config(['formatting.number_locale' => 'en']);
});

it('uses configured locale when not auto', function () {
    config(['formatting.number_locale' => 'en']);
    
    // Even if app locale is Kurdish, should use English
    app()->setLocale('ckb');
    
    $locale = NumberFormatter::getNumberLocale();
    
    expect($locale)->toBe('en');
});
