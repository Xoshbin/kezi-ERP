<?php

namespace Kezi\Inventory\Actions\Inventory;

use Illuminate\Support\Facades\DB;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Services\Inventory\StockQuantService;
use Kezi\Inventory\Services\Inventory\StockReservationService;

class ProcessInternalTransferAction
{
    public function __construct(
        protected StockQuantService $stockQuantService,
        protected StockReservationService $reservationService
    ) {}

    public function execute(StockMove $stockMove): void
    {
        DB::transaction(function () use ($stockMove) {
            // Internal transfer moves stock from 'from_location' to 'to_location'

            // 1. Pre-calculate reserved quantities before they are consumed (and deleted)
            $reservedQuantities = $stockMove->reservations()
                ->selectRaw('product_id, SUM(quantity) as total_reserved')
                ->groupBy('product_id')
                ->pluck('total_reserved', 'product_id');

            // 2. Consume reservations (this handles both qty and reserved_qty decrease at the source)
            $this->reservationService->consumeForMove($stockMove);

            // 3. Process each product line for the remaining outgoing part and the whole incoming part
            foreach ($stockMove->productLines as $productLine) {
                $reservedQty = (float) ($reservedQuantities[$productLine->product_id] ?? 0);

                if ($reservedQty < $productLine->quantity) {
                    $remainder = $productLine->quantity - $reservedQty;
                    // Deduct unreserved part from source
                    $this->stockQuantService->adjust(
                        $stockMove->company_id,
                        $productLine->product_id,
                        $productLine->from_location_id,
                        -$remainder,
                        0
                    );
                }

                // Add to destination
                $this->stockQuantService->applyForIncomingProductLine($productLine);
            }

            // Note: consumeForMove already deducted the reserved part (qty and reserved_qty)
            // So we just handled the unreserved part and the incoming part.
        });
    }
}
