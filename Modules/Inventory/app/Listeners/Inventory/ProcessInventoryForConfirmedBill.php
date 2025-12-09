<?php

namespace Modules\Inventory\Listeners\Inventory;

use Modules\Inventory\Actions\Inventory\UpdateProductInventoryStatsAction;
use Modules\Purchase\Events\VendorBillConfirmed;

class ProcessInventoryForConfirmedBill
{
    public function __construct(private readonly UpdateProductInventoryStatsAction $updateProductInventoryStatsAction) {}

    public function handle(VendorBillConfirmed $event): void
    {
        // Phase 1: No-op. Stock move creation and valuation are handled during posting via StockMoveConfirmed events.

    }
}
