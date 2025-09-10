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
            $product = $stockMove->product;
            if (! $product instanceof \App\Models\Product) {
                throw new \Exception('Product not found for stock move');
            }

            $sourceDocument = $stockMove->source;
            if (! $sourceDocument) {
                throw new \Exception('Stock move must have a source document');
            }

            $this->inventoryValuationService->processOutgoingStock(
                $product,
                $stockMove->quantity,
                $stockMove->move_date,
                $sourceDocument
            );
        });
    }
}
