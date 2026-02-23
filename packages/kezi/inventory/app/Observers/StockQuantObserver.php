<?php

namespace Kezi\Inventory\Observers;

use Kezi\Inventory\Events\ProductStockUpdated;
use Kezi\Inventory\Models\StockQuant;

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
        $this->dispatchProductStockUpdated($stockQuant);
    }

    /**
     * Handle the StockQuant "deleted" event.
     */
    public function deleted(StockQuant $stockQuant): void
    {
        $this->dispatchProductStockUpdated($stockQuant);
    }

    protected function dispatchProductStockUpdated(StockQuant $stockQuant): void
    {
        $product = $stockQuant->product;
        if ($product) {
            ProductStockUpdated::dispatch($product->id, $product->available_quantity);
        }
    }
}
