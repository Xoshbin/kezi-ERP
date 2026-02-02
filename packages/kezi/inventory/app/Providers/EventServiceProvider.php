<?php

namespace Kezi\Inventory\Providers;

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
            \Kezi\Inventory\Listeners\Inventory\ProcessInventoryForConfirmedBill::class,
            \Kezi\Inventory\Listeners\Purchase\CreateStockMovesOnVendorBillConfirmed::class,
        ],
        \Kezi\Purchase\Events\PurchaseOrderConfirmed::class => [
            \Kezi\Inventory\Listeners\Purchase\CreateStockPickingForPurchaseOrder::class,
        ],
        \Kezi\Sales\Events\InvoiceConfirmed::class => [
            \Kezi\Inventory\Listeners\Sales\CreateStockMovesOnInvoiceConfirmed::class,
        ],
        \Kezi\Inventory\Events\GoodsReceiptValidated::class => [
            \Kezi\Inventory\Listeners\Purchase\UpdatePurchaseOrderOnGoodsReceipt::class,
        ],
        \Kezi\Inventory\Events\AdjustmentDocumentPosted::class => [
            \Kezi\Inventory\Listeners\Adjustments\CreateStockMovesOnAdjustmentPosted::class,
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
