<?php

namespace Kezi\Inventory\Listeners\Inventory;

use Kezi\Inventory\Actions\Inventory\UpdateProductInventoryStatsAction;
use Kezi\Purchase\Events\VendorBillConfirmed;

class ProcessInventoryForConfirmedBill
{
    public function __construct(private readonly UpdateProductInventoryStatsAction $updateProductInventoryStatsAction) {}

    public function handle(VendorBillConfirmed $event): void
    {
        // Phase 1: No-op. Stock move creation and valuation are handled during posting via StockMoveConfirmed events.

    }
}
