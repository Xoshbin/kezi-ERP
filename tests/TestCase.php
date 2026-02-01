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
 * @property \Jmeryar\Foundation\Models\Partner $partner
 * @property \Jmeryar\Foundation\Models\Currency $currency
 * @property \Jmeryar\Product\Models\Product $product
 * @property \Jmeryar\Accounting\Models\Account $account
 * @property \Jmeryar\Accounting\Models\Account $inventoryAccount
 * @property \Jmeryar\Accounting\Models\Account $stockInputAccount
 * @property \Jmeryar\Accounting\Models\Account $cogsAccount
 * @property \Jmeryar\Inventory\Models\StockLocation $vendorLocation
 * @property \Jmeryar\Inventory\Models\StockLocation $stockLocation
 * @property \Jmeryar\Inventory\Models\StockLocation $adjustmentLocation
 * @property \Jmeryar\Inventory\Models\StockLocation $customerLocation
 * @property \Jmeryar\Foundation\Models\Partner $vendor
 *
 * @method void setupWithConfiguredCompany()
 * @method void setupInventoryTestEnvironment()
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

        // Set default locale for URL generation to fix issues with routes requiring {locale}
        $version = \Xoshbin\Pertuk\Services\DocumentationService::getAvailableVersions()[0] ?? 'v1.0';
        \Illuminate\Support\Facades\URL::defaults(['locale' => 'en', 'version' => $version]);

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
