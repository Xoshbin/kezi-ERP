<?php

namespace Tests;

use Illuminate\Foundation\Testing\Concerns\InteractsWithTime;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * This docblock provides type hints for PHPStan and IDEs for properties and methods
 * that are frequently added to test classes via traits (like WithConfiguredCompany)
 * or dynamically assigned in setup methods across the test suite.
 *
 * @property \App\Models\Company $company
 * @property \App\Models\User $user
 * @property \Modules\Foundation\Models\Partner $partner
 * @property \Modules\Foundation\Models\Currency $currency
 * @property \Modules\Product\Models\Product $product
 * @property \Modules\Accounting\Models\Account $account
 *
 * @method void setupWithConfiguredCompany()
 * @method \Mockery\MockInterface mock(string $abstract, \Closure $mock = null)
 */
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
