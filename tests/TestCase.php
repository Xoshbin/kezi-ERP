<?php

namespace Tests;

use Illuminate\Foundation\Testing\Concerns\InteractsWithTime;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use InteractsWithTime;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure Faker uses a known-good locale with all expected providers
        config()->set('app.faker_locale', 'en_US');

        // Use Laravel's default Faker generator with en_US locale for full provider set
        // Avoid overriding the generator manually to preserve all default providers.
        // The locale is set above via config('app.faker_locale').
    }

    /**
     * Customize the Faker instance to ensure required providers are registered.
     */
    protected function withFaker(): \Faker\Generator
    {
        return $this->app->make(\Faker\Generator::class);
    }
}
