<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

it('renders the docs index with at least one document', function () {
    $response = $this->get('/docs');

    $response->assertOk();
    // Expect the Payments guide (derived from docs/payments.md H1)
    $response->assertSee('Payments: Easy Guide for Everyone', false);
});

it('renders a doc page with TOC and breadcrumbs', function () {
    $response = $this->get('/docs/payments');

    $response->assertOk();
    $response->assertSee('<h1', false);
    $response->assertSee('Payments: Easy Guide for Everyone', false);

    // TOC should include a link to the "What is a Payment?" section
    $response->assertSee('href="#what-is-a-payment"', false);

    // Breadcrumbs should include Docs and page title
    $response->assertSee('Docs', false);
    $response->assertSee('Payments: Easy Guide for Everyone', false);

    // Code blocks should be marked for highlighting (hljs class present)
    $response->assertSee('hljs', false);
});

it('returns 304 Not Modified when If-Modified-Since matches', function () {
    $first = $this->get('/docs/payments');
    $first->assertOk();

    $lastModified = $first->headers->get('Last-Modified');
    expect($lastModified)->not->toBeNull();

    $second = $this->withHeaders([
        'If-Modified-Since' => $lastModified,
    ])->get('/docs/payments');

    $second->assertStatus(304);
});

it('returns 304 Not Modified when If-None-Match (ETag) matches', function () {
    $first = $this->get('/docs/payments');
    $first->assertOk();

    $etag = $first->headers->get('ETag');
    expect($etag)->not->toBeNull();

    $second = $this->withHeaders([
        'If-None-Match' => $etag,
    ])->get('/docs/payments');

    $second->assertStatus(304);
});

