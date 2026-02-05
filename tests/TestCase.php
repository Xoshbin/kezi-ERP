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
 * @property \Kezi\Foundation\Models\Partner $partner
 * @property \Kezi\Foundation\Models\Currency $currency
 * @property \Kezi\Product\Models\Product $product
 * @property \Kezi\Accounting\Models\Account $account
 * @property \Kezi\Accounting\Models\Account $inventoryAccount
 * @property \Kezi\Accounting\Models\Account $stockInputAccount
 * @property \Kezi\Accounting\Models\Account $cogsAccount
 * @property \Kezi\Inventory\Models\StockLocation $vendorLocation
 * @property \Kezi\Inventory\Models\StockLocation $stockLocation
 * @property \Kezi\Inventory\Models\StockLocation $adjustmentLocation
 * @property \Kezi\Inventory\Models\StockLocation $customerLocation
 * @property \Kezi\Foundation\Models\Partner $vendor
 * @property \Kezi\Sales\Actions\Sales\CreateQuoteAction&\Mockery\MockInterface $createAction
 * @property \Kezi\Sales\Actions\Sales\UpdateQuoteAction&\Mockery\MockInterface $updateAction
 * @property \Kezi\Sales\Actions\Sales\SendQuoteAction&\Mockery\MockInterface $sendAction
 * @property \Kezi\Sales\Actions\Sales\AcceptQuoteAction&\Mockery\MockInterface $acceptAction
 * @property \Kezi\Sales\Actions\Sales\RejectQuoteAction&\Mockery\MockInterface $rejectAction
 * @property \Kezi\Sales\Actions\Sales\CancelQuoteAction&\Mockery\MockInterface $cancelAction
 * @property \Kezi\Sales\Actions\Sales\ConvertQuoteToSalesOrderAction&\Mockery\MockInterface $convertToOrderAction
 * @property \Kezi\Sales\Actions\Sales\ConvertQuoteToInvoiceAction&\Mockery\MockInterface $convertToInvoiceAction
 * @property \Kezi\Sales\Actions\Sales\CreateQuoteRevisionAction&\Mockery\MockInterface $revisionAction
 * @property \Kezi\Sales\Services\QuoteService $service
 * @property \Kezi\Accounting\Models\Account $incomeAccount
 * @property \Kezi\Sales\Actions\Sales\CreateInvoiceLineAction&\Mockery\MockInterface $createInvoiceLineAction
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
