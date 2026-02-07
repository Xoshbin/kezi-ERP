<?php

use function Pest\Laravel\get;

test('it redirects /docs to the default locale index', function () {
    $response = get('/docs');

    $defaultLocale = config('pertuk.default_locale', 'en');
    
    $response->assertRedirect(route('pertuk.docs.show', [
        'locale' => $defaultLocale,
        'slug' => 'index',
    ]));
});

test('it can load documentation pages', function () {
    $defaultLocale = config('pertuk.default_locale', 'en');
    
    $response = get("/docs/{$defaultLocale}/index");

    $response->assertStatus(200);
});

test('it returns 404 for unsupported locales in docs', function () {
    $response = get('/docs/invalid-locale/index');

    $response->assertStatus(404);
});
