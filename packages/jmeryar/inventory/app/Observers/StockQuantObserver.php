<?php

namespace Jmeryar\Inventory\Observers;

use Jmeryar\Inventory\Models\StockQuant;

/**
 * StockQuantObserver
 *
 * This observer handles side effects when StockQuant records are modified.
 * Note: Product.quantity_on_hand has been deprecated in favor of computed
 * accessors that read from StockQuant directly.
 */
class StockQuantObserver
{
    /**
     * Handle the StockQuant "saved" event.
     *
     * Previously this observer synced changes to Product.quantity_on_hand,
     * but that column has been deprecated. The Product model now computes
     * quantity_on_hand via getQuantityOnHandAttribute() from StockQuant totals.
     */
    public function saved(StockQuant $stockQuant): void
    {
        // No-op: Product.quantity_on_hand is now a computed accessor
    }

    /**
     * Handle the StockQuant "deleted" event.
     */
    public function deleted(StockQuant $stockQuant): void
    {
        // No-op: Product.quantity_on_hand is now a computed accessor
    }
}
