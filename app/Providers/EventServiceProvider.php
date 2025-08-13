<?php

namespace App\Providers;

use App\Events\InvoiceConfirmed;
use App\Events\VendorBillConfirmed;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use App\Events\Inventory\StockMoveConfirmed;
use App\Listeners\Inventory\HandleStockMoveConfirmation;
use App\Listeners\Asset\CreateAssetFromVendorBillListener;
use App\Listeners\Purchases\CreateInterCompanyBillListener;
use App\Listeners\Inventory\ProcessInventoryForConfirmedBill;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

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
        InvoiceConfirmed::class => [
            CreateInterCompanyBillListener::class,
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
