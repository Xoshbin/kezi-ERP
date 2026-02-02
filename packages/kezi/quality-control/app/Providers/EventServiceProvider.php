<?php

namespace Kezi\QualityControl\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Kezi\Inventory\Events\StockPickingValidated::class => [
            \Kezi\QualityControl\Listeners\CreateQualityChecksForStockPicking::class,
        ],
        \Kezi\Manufacturing\Events\ManufacturingOrderConfirmed::class => [
            \Kezi\QualityControl\Listeners\CreateQualityChecksForManufacturing::class,
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
    protected function configureEmailVerification(): void
    {
        //
    }
}
