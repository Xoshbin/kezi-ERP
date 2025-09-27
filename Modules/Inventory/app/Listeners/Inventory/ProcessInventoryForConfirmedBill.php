<?php

namespace Modules\Inventory\Listeners\Inventory;

use App\Actions\Inventory\UpdateProductInventoryStatsAction;
use App\Events\VendorBillConfirmed;

class ProcessInventoryForConfirmedBill
{
    public function __construct(private readonly UpdateProductInventoryStatsAction $updateProductInventoryStatsAction) {}

    public function handle(VendorBillConfirmed $event): void
    {
        // Phase 1: No-op. Stock move creation and valuation are handled during posting via StockMoveConfirmed events.
        return;
    }
}
