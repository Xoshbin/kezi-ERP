<?php

namespace Jmeryar\Accounting\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Jmeryar\Purchase\Events\VendorBillConfirmed::class => [
            \Jmeryar\Accounting\Listeners\Asset\CreateAssetFromVendorBillListener::class,
            \Jmeryar\Accounting\Listeners\Deferred\CreateDeferredExpenseFromVendorBill::class,
        ],
        \Jmeryar\Sales\Events\InvoiceConfirmed::class => [
            \Jmeryar\Accounting\Listeners\Consolidation\CreateInterCompanyVendorBillListener::class,
            \Jmeryar\Accounting\Listeners\Deferred\CreateDeferredRevenueFromInvoice::class,
        ],
        \Jmeryar\Accounting\Events\FiscalYearClosed::class => [
            \Jmeryar\Accounting\Listeners\UpdateLockDateOnFiscalYearClose::class,
        ],
        \Jmeryar\Accounting\Events\FiscalPeriodClosed::class => [
            \Jmeryar\Accounting\Listeners\UpdateLockDateOnFiscalPeriodClose::class,
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
