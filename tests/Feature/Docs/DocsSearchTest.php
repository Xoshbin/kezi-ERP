<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

it('shows search input in docs header', function () {
    $response = $this->get('/docs');

    $response->assertOk();
    $response->assertSee('placeholder="Search docs"', false);
});

it('returns docs search index JSON', function () {
    $response = $this->get('/docs/index.json');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/json');
    $response->assertSee('payments', false);
});

