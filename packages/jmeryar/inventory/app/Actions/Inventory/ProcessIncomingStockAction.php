<?php

namespace Jmeryar\Inventory\Actions\Inventory;

use Brick\Money\Money;
use Exception;
use Illuminate\Support\Facades\DB;
use Jmeryar\Inventory\Models\StockMove;
use Jmeryar\Inventory\Models\StockMoveProductLine;
use Jmeryar\Inventory\Services\Inventory\InventoryValuationService;
use Jmeryar\Inventory\Services\Inventory\StockQuantService;
use Jmeryar\Product\Models\Product;
use Jmeryar\Purchase\Models\PurchaseOrder;
use Jmeryar\Purchase\Models\VendorBill;
use Jmeryar\Purchase\Models\VendorBillLine;

class ProcessIncomingStockAction
{
    public function __construct(
        protected InventoryValuationService $inventoryValuationService,
        protected \Jmeryar\Foundation\Services\CurrencyConverterService $currencyConverter,
        protected StockQuantService $stockQuantService,
    ) {}

    public function execute(StockMove $stockMove): void
    {
        DB::transaction(function () use ($stockMove) {
            // Process inventory valuation and consolidated journal entry
            $this->inventoryValuationService->createConsolidatedManualStockMoveJournalEntry($stockMove);

            // Process per-line side effects (Quants, PO status)
            foreach ($stockMove->productLines as $productLine) {
                // Update quants for destination location
                $this->stockQuantService->applyForIncomingProductLine($productLine);

                // Update Purchase Order status if this stock move is related to a PO
                $this->updatePurchaseOrderStatus($stockMove, $productLine);
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
        // For manual stock moves without a source document, use the stock move itself as the source
        if (! $sourceDocument) {
            $sourceDocument = $stockMove;
        }

        // Ensure cost is in company base currency
        $costPerUnitCompany = $costPerUnit;
        $companyCurrency = $product->company->currency;
        if ($sourceDocument instanceof VendorBill || $sourceDocument instanceof \Jmeryar\Purchase\Models\PurchaseOrderLine) {
            // Use the source document's stored exchange rate for consistency
            $exchangeRate = 1.0;
            $docCurrencyId = null;

            if ($sourceDocument instanceof VendorBill) {
                $exchangeRate = $sourceDocument->exchange_rate_at_creation ?? 1.0;
                $docCurrencyId = $sourceDocument->currency_id;
            } elseif ($sourceDocument instanceof \Jmeryar\Purchase\Models\PurchaseOrderLine) {
                $exchangeRate = $sourceDocument->purchaseOrder->exchange_rate_at_creation ?? 1.0;
                $docCurrencyId = $sourceDocument->purchaseOrder->currency_id;
            }

            if ($docCurrencyId !== $companyCurrency->id) {
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

        // Update Purchase Order status if this stock move is related to a PO
        $this->updatePurchaseOrderStatus($stockMove, $productLine);
    }

    /**
     * Update Purchase Order status when stock moves are completed.
     */
    protected function updatePurchaseOrderStatus(StockMove $stockMove, StockMoveProductLine $productLine): void
    {
        if ($stockMove->source_type === PurchaseOrder::class && $stockMove->source_id) {
            $purchaseOrder = PurchaseOrder::find($stockMove->source_id);
            if ($purchaseOrder) {
                // Find the corresponding PO line for this product
                $poLine = $purchaseOrder->lines()
                    ->where('product_id', $productLine->product_id)
                    ->first();

                if ($poLine) {
                    // Increment the received quantity
                    $poLine->quantity_received += $productLine->quantity;
                    $poLine->save();

                    // Update PO status based on total received quantities
                    $purchaseOrder->updateStatusBasedOnReceipts(fromInventoryOperation: true);
                    $purchaseOrder->save();
                }
            }
        }
    }

    /**
     * Extract the cost per unit from the source document
     */
    private function extractCostFromSource(StockMove $stockMove, StockMoveProductLine $productLine): Money
    {
        // Use enhanced valuation logic which supports various sources and fallbacks (e.g. manual moves)
        $costResult = $this->inventoryValuationService->calculateIncomingCostPerUnitEnhanced(
            $productLine->product,
            $stockMove,
            true // Allow fallbacks for manual moves
        );

        return $costResult->cost;
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
        if ($line->tax_id && $line->total_line_tax->isPositive() && $line->tax && ! $line->tax->is_recoverable) {
            $taxPerUnit = $line->total_line_tax->dividedBy($line->quantity);
            $unitPrice = $unitPrice->plus($taxPerUnit);
        }

        return $unitPrice;
    }

    /**
     * Extract cost from purchase order line
     */
    private function extractCostFromPurchaseOrderLine(StockMoveProductLine $productLine, \Jmeryar\Purchase\Models\PurchaseOrderLine $poLine): Money
    {
        $unitPrice = $poLine->unit_price;

        // Note: Tax handling for POs mirrors Vendor Bills.
        // If tax is non-recoverable, it should increase the valuation cost.
        // Assuming PO Line has access to tax details or calculated tax amount.
        // PO Line has `total_line_tax`. Tax definition? `tax_id`?
        // Let's assume for now we use unit_price.
        // Improvement: Handle non-recoverable tax if PO has it.

        return $unitPrice;
    }
}
