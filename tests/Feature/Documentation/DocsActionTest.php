<?php

use Kezi\Foundation\Filament\Actions\DocsAction;

test('docs action generates valid url with locale', function () {
    // Mock the documentation service version if needed, or rely on default
    $action = DocsAction::make('getting-started');

    $url = $action->getUrl();

    expect($url)->not->toBeNull();
    // It should contain the current locale (en by default in tests)
    expect($url)->toContain('/en/');
    // It should contain the version
    expect($url)->toMatch('/v[0-9.]+/');
    // It should contain the mapped slug
    expect($url)->toContain('getting-started');
});

test('docs action generates valid url for specific mapped slug', function () {
    $action = DocsAction::make('payments');

    $url = $action->getUrl();

    expect($url)->toContain('how-to/payments');
});
