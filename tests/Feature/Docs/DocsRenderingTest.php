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
    // Expect the Payments guide (now in User Guide directory, displayed as "Payments")
    $response->assertSee('Payments', false);
});

it('renders a doc page with TOC and breadcrumbs', function () {
    $response = $this->get('/docs/User Guide/payments');

    $response->assertOk();
    $response->assertSee('<h1', false);
    $response->assertSee('Payments: Payments', false);

    // TOC should include a link to the "What is a Payment?" section
    $response->assertSee('href="#what-is-a-payment"', false);

    // Breadcrumbs should include Docs and page title
    $response->assertSee('Docs', false);
    $response->assertSee('Payments: Payments', false);

    // Code blocks should be marked for highlighting (hljs class present)
    $response->assertSee('hljs', false);
});

it('returns 304 Not Modified when If-Modified-Since matches', function () {
    $first = $this->get('/docs/User Guide/payments');
    $first->assertOk();

    $lastModified = $first->headers->get('Last-Modified');
    expect($lastModified)->not->toBeNull();

    $second = $this->withHeaders([
        'If-Modified-Since' => $lastModified,
    ])->get('/docs/User Guide/payments');

    $second->assertStatus(304);
});

it('returns 304 Not Modified when If-None-Match (ETag) matches', function () {
    $first = $this->get('/docs/User Guide/payments');
    $first->assertOk();

    $etag = $first->headers->get('ETag');
    expect($etag)->not->toBeNull();

    $second = $this->withHeaders([
        'If-None-Match' => $etag,
    ])->get('/docs/User Guide/payments');

    $second->assertStatus(304);
});

