<?php

namespace Modules\Inventory\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Modules\Inventory\Events\Inventory\StockMoveConfirmed::class => [
            \Modules\Inventory\Listeners\Inventory\HandleStockMoveConfirmation::class,
        ],
        \Modules\Purchase\Events\VendorBillConfirmed::class => [
            \Modules\Inventory\Listeners\Inventory\ProcessInventoryForConfirmedBill::class,
        ],
        \Modules\Purchase\Events\PurchaseOrderConfirmed::class => [
            \Modules\Inventory\Listeners\Purchase\CreateStockPickingForPurchaseOrder::class,
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
    }
}
