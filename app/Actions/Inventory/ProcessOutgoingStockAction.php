<?php

namespace App\Actions\Inventory;

use App\Models\StockMove;
use App\Services\Inventory\InventoryValuationService;
use Illuminate\Support\Facades\DB;

class ProcessOutgoingStockAction
{
    public function __construct(protected InventoryValuationService $inventoryValuationService) {}

    public function execute(StockMove $stockMove): void
    {
        DB::transaction(function () use ($stockMove) {
            $this->inventoryValuationService->processOutgoingStock(
                $stockMove->product,
                $stockMove->quantity,
                $stockMove->move_date,
                $stockMove->source
            );
        });
    }
}
