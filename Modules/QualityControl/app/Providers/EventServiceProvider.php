<?php

namespace Modules\QualityControl\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Modules\Inventory\Events\StockPickingValidated::class => [
            \Modules\QualityControl\Listeners\CreateQualityChecksForStockPicking::class,
        ],
        \Modules\Manufacturing\Events\ManufacturingOrderCompleted::class => [
            \Modules\QualityControl\Listeners\CreateQualityChecksForManufacturing::class,
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
