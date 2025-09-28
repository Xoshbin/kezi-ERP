<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure Faker uses a known-good locale with all expected providers
        config()->set('app.faker_locale', 'en_US');
    }
}
