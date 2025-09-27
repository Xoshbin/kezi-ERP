<?php

namespace App\Actions\Inventory;

use App\Models\StockMove;
use App\Services\Inventory\InventoryValuationService;
use App\Services\Inventory\StockQuantService;
use App\Services\Inventory\StockReservationService;
use Illuminate\Support\Facades\DB;

class ProcessOutgoingStockAction
{
    public function __construct(
        protected InventoryValuationService $inventoryValuationService,
        protected StockQuantService $stockQuantService,
        protected StockReservationService $stockReservationService,
    ) {}

    public function execute(StockMove $stockMove): void
    {
        DB::transaction(function () use ($stockMove) {
            $sourceDocument = $stockMove->source;
            if (! $sourceDocument) {
                throw new \Exception('Stock move must have a source document');
            }

            // Handle both old structure (direct product) and new structure (product lines)
            if (isset($stockMove->product_id) && $stockMove->product_id) {
                // Old structure - single product
                $product = $stockMove->product;
                if (! $product instanceof \App\Models\Product) {
                    throw new \Exception('Product not found for stock move');
                }

                $this->inventoryValuationService->processOutgoingStock(
                    $product,
                    $stockMove->quantity,
                    $stockMove->move_date,
                    $sourceDocument
                );
            } else {
                // New structure - multiple product lines
                foreach ($stockMove->productLines as $productLine) {
                    $product = $productLine->product;
                    if (! $product instanceof \App\Models\Product) {
                        throw new \Exception('Product not found for product line');
                    }

                    $this->inventoryValuationService->processOutgoingStock(
                        $product,
                        $productLine->quantity,
                        $stockMove->move_date,
                        $sourceDocument
                    );
                }
            }

            // Phase 2B: Deduct quants by consuming reservations only (no oversell)
            $this->stockReservationService->consumeForMove($stockMove);
        });
    }
}
