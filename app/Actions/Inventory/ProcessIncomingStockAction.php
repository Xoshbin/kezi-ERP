<?php

namespace App\Actions\Inventory;

use App\Models\StockMove;
use App\Models\VendorBill;
use App\Services\Inventory\InventoryValuationService;
use Brick\Money\Money;
use Exception;
use Illuminate\Support\Facades\DB;

class ProcessIncomingStockAction
{
    public function __construct(protected InventoryValuationService $inventoryValuationService) {}

    public function execute(StockMove $stockMove): void
    {
        DB::transaction(function () use ($stockMove) {
            // Extract the cost per unit from the source document
            $costPerUnit = $this->extractCostFromSource($stockMove);

            $this->inventoryValuationService->processIncomingStock(
                $stockMove->product,
                $stockMove->quantity,
                $costPerUnit,
                $stockMove->move_date,
                $stockMove->source
            );
        });
    }

    /**
     * Extract the cost per unit from the source document
     */
    private function extractCostFromSource(StockMove $stockMove): Money
    {
        $sourceDocument = $stockMove->source;

        if ($sourceDocument instanceof VendorBill) {
            return $this->extractCostFromVendorBill($stockMove, $sourceDocument);
        }

        // For other source types (future: inventory adjustments, transfers, etc.)
        // we can add more extraction logic here

        throw new Exception('Unable to extract cost from source document type: '.get_class($sourceDocument));
    }

    /**
     * Extract cost from vendor bill line
     */
    private function extractCostFromVendorBill(StockMove $stockMove, VendorBill $vendorBill): Money
    {
        // Find the vendor bill line that corresponds to this product
        $line = $vendorBill->lines()
            ->where('product_id', $stockMove->product_id)
            ->first();

        if (! ($line instanceof \App\Models\VendorBillLine)) {
            throw new Exception("No vendor bill line found for product {$stockMove->product_id} in vendor bill {$vendorBill->getKey()}");
        }

        return $line->unit_price;
    }
}
