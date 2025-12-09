<?php

namespace Modules\Inventory\Observers;

use Modules\Inventory\Models\StockQuant;
use Modules\Inventory\Services\Inventory\StockQuantService;

class StockQuantObserver
{
    public function saved(StockQuant $stockQuant): void
    {
        $this->updateProductQuantity($stockQuant);
    }

    public function deleted(StockQuant $stockQuant): void
    {
        $this->updateProductQuantity($stockQuant);
    }

    protected function updateProductQuantity(StockQuant $stockQuant): void
    {
        // Prevent infinite loops if product update triggers something that saves quant
        if ($stockQuant->isDirty('quantity')) {
            $stockQuantService = app(StockQuantService::class);
            $totalQuantity = $stockQuantService->getTotalQuantity($stockQuant->company_id, $stockQuant->product_id);

            $stockQuant->product()->update(['quantity_on_hand' => $totalQuantity]);
        } elseif ($stockQuant->wasRecentlyCreated) {
            // For newly created records where isDirty might behave differently depending on how it was created
            $stockQuantService = app(StockQuantService::class);
            $totalQuantity = $stockQuantService->getTotalQuantity($stockQuant->company_id, $stockQuant->product_id);

            $stockQuant->product()->update(['quantity_on_hand' => $totalQuantity]);
        }
    }
}
