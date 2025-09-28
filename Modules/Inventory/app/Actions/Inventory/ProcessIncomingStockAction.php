<?php

namespace Modules\Inventory\Actions\Inventory;

use Exception;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;
use Modules\Inventory\Models\StockMove;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Models\VendorBillLine;
use Modules\Inventory\Models\StockMoveProductLine;
use Modules\Inventory\Services\Inventory\StockQuantService;
use Modules\Inventory\Services\Inventory\InventoryValuationService;

class ProcessIncomingStockAction
{
    public function __construct(
        protected InventoryValuationService $inventoryValuationService,
        protected \Modules\Foundation\Services\CurrencyConverterService $currencyConverter,
        protected StockQuantService $stockQuantService,
    ) {}

    public function execute(StockMove $stockMove): void
    {
        DB::transaction(function () use ($stockMove) {
            // Process each product line
            foreach ($stockMove->productLines as $productLine) {
                $this->processProductLine($stockMove, $productLine);
            }
        });
    }

    private function processProductLine(StockMove $stockMove, StockMoveProductLine $productLine): void
    {
        // Extract the cost per unit from the source document
        $costPerUnit = $this->extractCostFromSource($stockMove, $productLine);

        $product = $productLine->product;
        if (! $product instanceof Product) {
            throw new Exception('Product not found for product line');
        }

        $sourceDocument = $stockMove->source;
        if (! $sourceDocument) {
            throw new Exception('Stock move must have a source document');
        }

        // Ensure cost is in company base currency
        $costPerUnitCompany = $costPerUnit;
        $companyCurrency = $product->company->currency;
        if ($sourceDocument instanceof VendorBill) {
            // Use the vendor bill's stored exchange rate for consistency
            $exchangeRate = $sourceDocument->exchange_rate_at_creation ?? 1.0;
            if ($sourceDocument->currency_id !== $companyCurrency->id) {
                $costPerUnitCompany = $this->currencyConverter->convertWithRate(
                    $costPerUnit,
                    $exchangeRate,
                    $companyCurrency->code,
                    false
                );
            }
        }

        $this->inventoryValuationService->processIncomingStock(
            $product,
            $productLine->quantity,
            $costPerUnitCompany,
            $stockMove->move_date,
            $sourceDocument
        );

        // Update quants for destination location
        $this->stockQuantService->applyForIncomingProductLine($productLine);
    }

    /**
     * Extract the cost per unit from the source document
     */
    private function extractCostFromSource(StockMove $stockMove, StockMoveProductLine $productLine): Money
    {
        $sourceDocument = $stockMove->source;

        if ($sourceDocument instanceof VendorBill) {
            return $this->extractCostFromVendorBill($productLine, $sourceDocument);
        }

        // For other source types (future: inventory adjustments, transfers, etc.)
        // we can add more extraction logic here

        if (! $sourceDocument) {
            throw new Exception('Source document is null');
        }

        throw new Exception('Unable to extract cost from source document type: ' . get_class($sourceDocument));
    }

    /**
     * Extract cost from vendor bill line
     */
    private function extractCostFromVendorBill(StockMoveProductLine $productLine, VendorBill $vendorBill): Money
    {
        // Find the vendor bill line that corresponds to this product
        $line = $vendorBill->lines()
            ->with('tax') // Load tax relationship to check if it should be capitalized
            ->where('product_id', $productLine->product_id)
            ->first();

        if (! ($line instanceof VendorBillLine)) {
            throw new Exception("No vendor bill line found for product {$productLine->product_id} in vendor bill {$vendorBill->getKey()}");
        }

        $unitPrice = $line->unit_price;

        // If tax is non-recoverable, include it in the unit cost
        if ($line->tax_id && $line->total_line_tax->isPositive() && $line->tax && !$line->tax->is_recoverable) {
            $taxPerUnit = $line->total_line_tax->dividedBy($line->quantity);
            $unitPrice = $unitPrice->plus($taxPerUnit);
        }

        return $unitPrice;
    }
}
