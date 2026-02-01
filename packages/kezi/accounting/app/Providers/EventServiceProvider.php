<?php

namespace Kezi\Accounting\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Kezi\Purchase\Events\VendorBillConfirmed::class => [
            \Kezi\Accounting\Listeners\Asset\CreateAssetFromVendorBillListener::class,
            \Kezi\Accounting\Listeners\Deferred\CreateDeferredExpenseFromVendorBill::class,
        ],
        \Kezi\Sales\Events\InvoiceConfirmed::class => [
            \Kezi\Accounting\Listeners\Consolidation\CreateInterCompanyVendorBillListener::class,
            \Kezi\Accounting\Listeners\Deferred\CreateDeferredRevenueFromInvoice::class,
        ],
        \Kezi\Accounting\Events\FiscalYearClosed::class => [
            \Kezi\Accounting\Listeners\UpdateLockDateOnFiscalYearClose::class,
        ],
        \Kezi\Accounting\Events\FiscalPeriodClosed::class => [
            \Kezi\Accounting\Listeners\UpdateLockDateOnFiscalPeriodClose::class,
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
