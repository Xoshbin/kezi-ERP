<?php

namespace Kezi\Inventory\Actions\Inventory;

use Illuminate\Support\Facades\DB;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Services\Inventory\InventoryValuationService;
use Kezi\Inventory\Services\Inventory\StockQuantService;
use Kezi\Inventory\Services\Inventory\StockReservationService;

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
            // 1. Process inventory valuation and consolidated journal entry
            // This handles COGS calculation, layer consumption (FIFO/LIFO), and JE creation
            $this->inventoryValuationService->createConsolidatedManualStockMoveJournalEntry($stockMove);

            // 2. Pre-calculate reserved quantities before they are consumed (and deleted)
            $reservedQuantities = $stockMove->reservations()
                ->selectRaw('product_id, SUM(quantity) as total_reserved')
                ->groupBy('product_id')
                ->pluck('total_reserved', 'product_id');

            // 3. Consume reservations (this handles both qty and reserved_qty decrease at the source)
            $this->stockReservationService->consumeForMove($stockMove);

            // 4. Handle unreserved quantities (e.g. for direct POS sales or manual moves)
            foreach ($stockMove->productLines as $productLine) {
                $reservedQty = (float) ($reservedQuantities[$productLine->product_id] ?? 0);

                if ($reservedQty < $productLine->quantity) {
                    $remainder = $productLine->quantity - $reservedQty;

                    // Deduct unreserved part from source location
                    $this->stockQuantService->adjust(
                        $stockMove->company_id,
                        $productLine->product_id,
                        $productLine->from_location_id,
                        -$remainder,
                        0
                    );
                }
            }
        });
    }
}
