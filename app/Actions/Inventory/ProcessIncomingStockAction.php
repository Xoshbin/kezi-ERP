<?php

namespace App\Actions\Inventory;

use App\Models\StockMove;
use App\Services\Inventory\InventoryValuationService;
use Illuminate\Support\Facades\DB;

class ProcessIncomingStockAction
{
    public function __construct(protected InventoryValuationService $inventoryValuationService)
    {
    }

    public function execute(StockMove $stockMove): void
    {
        DB::transaction(function () use ($stockMove) {
            // We need to get the cost from the source document, which is not yet implemented.
            // For now, we'll assume a placeholder cost.
            $costPerUnit = \Brick\Money\Money::of(10, 'USD'); // Placeholder
            $this->inventoryValuationService->processIncomingStock(
                $stockMove->product,
                $stockMove->quantity,
                $costPerUnit,
                $stockMove->move_date,
                $stockMove->source
            );
        });
    }
}
