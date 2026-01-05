<?php

namespace Modules\Accounting\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Modules\Purchase\Events\VendorBillConfirmed::class => [
            \Modules\Accounting\Listeners\Asset\CreateAssetFromVendorBillListener::class,
        ],
        \Modules\Sales\Events\InvoiceConfirmed::class => [
            \Modules\Accounting\Listeners\Consolidation\CreateInterCompanyVendorBillListener::class,
        ],
        \Modules\Accounting\Events\FiscalYearClosed::class => [
            \Modules\Accounting\Listeners\UpdateLockDateOnFiscalYearClose::class,
        ],
        \Modules\Accounting\Events\FiscalPeriodClosed::class => [
            \Modules\Accounting\Listeners\UpdateLockDateOnFiscalPeriodClose::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
