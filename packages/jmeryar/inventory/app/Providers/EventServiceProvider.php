<?php

namespace Jmeryar\Inventory\Providers;

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
            \Jmeryar\Inventory\Listeners\Inventory\ProcessInventoryForConfirmedBill::class,
            \Jmeryar\Inventory\Listeners\Purchase\CreateStockMovesOnVendorBillConfirmed::class,
        ],
        \Jmeryar\Purchase\Events\PurchaseOrderConfirmed::class => [
            \Jmeryar\Inventory\Listeners\Purchase\CreateStockPickingForPurchaseOrder::class,
        ],
        \Jmeryar\Sales\Events\InvoiceConfirmed::class => [
            \Jmeryar\Inventory\Listeners\Sales\CreateStockMovesOnInvoiceConfirmed::class,
        ],
        \Jmeryar\Inventory\Events\GoodsReceiptValidated::class => [
            \Jmeryar\Inventory\Listeners\Purchase\UpdatePurchaseOrderOnGoodsReceipt::class,
        ],
        \Jmeryar\Inventory\Events\AdjustmentDocumentPosted::class => [
            \Jmeryar\Inventory\Listeners\Adjustments\CreateStockMovesOnAdjustmentPosted::class,
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
