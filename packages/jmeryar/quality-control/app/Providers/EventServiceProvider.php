<?php

namespace Jmeryar\QualityControl\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Jmeryar\Inventory\Events\StockPickingValidated::class => [
            \Jmeryar\QualityControl\Listeners\CreateQualityChecksForStockPicking::class,
        ],
        \Jmeryar\Manufacturing\Events\ManufacturingOrderConfirmed::class => [
            \Jmeryar\QualityControl\Listeners\CreateQualityChecksForManufacturing::class,
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
