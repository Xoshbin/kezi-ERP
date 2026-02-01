<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Jmeryar\Accounting\Listeners\Asset\CreateAssetFromVendorBillListener;
use Jmeryar\Inventory\Events\Inventory\StockMoveConfirmed;
use Jmeryar\Inventory\Listeners\Inventory\HandleStockMoveConfirmation;
use Jmeryar\Inventory\Listeners\Inventory\ProcessInventoryForConfirmedBill;
use Jmeryar\Purchase\Events\VendorBillConfirmed;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        StockMoveConfirmed::class => [
            HandleStockMoveConfirmation::class,
        ],
        VendorBillConfirmed::class => [
            ProcessInventoryForConfirmedBill::class,
            CreateAssetFromVendorBillListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return true;
    }
}
