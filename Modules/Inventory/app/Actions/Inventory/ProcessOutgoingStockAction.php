<?php

namespace Modules\Inventory\Actions\Inventory;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Services\Inventory\InventoryValuationService;
use Modules\Inventory\Services\Inventory\StockQuantService;
use Modules\Inventory\Services\Inventory\StockReservationService;
use Modules\Product\Models\Product;

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
            // Process inventory valuation and consolidated journal entry
            // This handles COGS calculation, layer consumption (FIFO/LIFO), and JE creation
            $this->inventoryValuationService->createConsolidatedManualStockMoveJournalEntry($stockMove);

            // Phase 2B: Deduct quants by consuming reservations only (no oversell)
            // Note: createConsolidatedManualStockMoveJournalEntry updates quantity_on_hand per product,
            // but consumeForMove handles specific location quants and reservations.
            $this->stockReservationService->consumeForMove($stockMove);
        });
    }
}
