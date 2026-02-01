<?php

namespace Jmeryar\Purchase\Services;

use Jmeryar\Inventory\Enums\Inventory\StockPickingState;
use Jmeryar\Inventory\Models\StockPicking;
use Jmeryar\Inventory\Services\Inventory\GoodsReceiptService;
use Jmeryar\Purchase\Enums\Purchases\ThreeWayMatchStatus;
use Jmeryar\Purchase\Models\VendorBill;
use Jmeryar\Purchase\Models\VendorBillLine;

/**
 * Service for three-way matching between Purchase Order, Goods Receipt (GRN), and Vendor Bill.
 *
 * Three-way matching is a standard procurement control that verifies:
 * 1. PO exists and is confirmed
 * 2. Goods have been physically received (GRN validated)
 * 3. Bill quantities and prices match the PO
 *
 * This helps prevent:
 * - Paying for goods not yet received
 * - Paying for incorrect quantities
 * - Paying incorrect prices
 */
class ThreeWayMatchingService
{
    public function __construct(
        private readonly GoodsReceiptService $goodsReceiptService,
    ) {}

    /**
     * Determine the three-way matching status for a vendor bill.
     */
    public function getMatchingStatus(VendorBill $bill): ThreeWayMatchStatus
    {
        // No PO linked = not applicable
        if (! $bill->purchase_order_id) {
            return ThreeWayMatchStatus::NotApplicable;
        }

        $po = $bill->purchaseOrder;
        if (! $po) {
            return ThreeWayMatchStatus::NotApplicable;
        }

        // Check if any goods have been received
        $hasValidatedGrn = $this->goodsReceiptService->hasValidatedGoodsReceipt($po);

        if (! $hasValidatedGrn) {
            return ThreeWayMatchStatus::PendingReceipt;
        }

        // Check for partial receipt
        if ($po->isPartiallyReceived()) {
            return ThreeWayMatchStatus::PartiallyReceived;
        }

        // Check quantity matching
        $quantityMatch = $this->checkQuantityMatch($bill);
        if (! $quantityMatch) {
            return ThreeWayMatchStatus::QuantityMismatch;
        }

        // Check price matching
        $priceMatch = $this->checkPriceMatch($bill);
        if (! $priceMatch) {
            return ThreeWayMatchStatus::PriceMismatch;
        }

        return ThreeWayMatchStatus::FullyMatched;
    }

    /**
     * Update the three-way match status on a vendor bill.
     */
    public function updateMatchStatus(VendorBill $bill): VendorBill
    {
        $status = $this->getMatchingStatus($bill);
        $bill->three_way_match_status = $status->value;
        $bill->save();

        return $bill;
    }

    /**
     * Validate that a vendor bill can be posted based on three-way matching rules.
     *
     * @throws \InvalidArgumentException If goods have not been received
     */
    public function validateForPosting(VendorBill $bill, bool $strictMode = true): bool
    {
        $status = $this->getMatchingStatus($bill);

        // Not applicable (no PO) - always allowed
        if ($status === ThreeWayMatchStatus::NotApplicable) {
            return true;
        }

        // In strict mode, block posting if goods not received
        if ($strictMode && $status->blocksPosting()) {
            throw new \InvalidArgumentException(
                'Cannot post vendor bill: goods have not been received. Please validate the Goods Receipt first.'
            );
        }

        // Warn about mismatches but don't block
        if ($status->hasMismatch()) {
            // Log warning but allow posting
            \Illuminate\Support\Facades\Log::warning(
                "Vendor Bill {$bill->bill_reference} posted with matching issues: {$status->value}"
            );
        }

        return true;
    }

    /**
     * Get the linked GRN for a vendor bill.
     */
    public function getLinkedGoodsReceipt(VendorBill $bill): ?StockPicking
    {
        if ($bill->stock_picking_id) {
            return StockPicking::find($bill->stock_picking_id);
        }

        // If no direct link but has PO, find the validated GRN
        if ($bill->purchase_order_id) {
            return StockPicking::where('purchase_order_id', $bill->purchase_order_id)
                ->where('state', StockPickingState::Done)
                ->first();
        }

        return null;
    }

    /**
     * Check if bill quantities match PO/GRN quantities.
     */
    private function checkQuantityMatch(VendorBill $bill): bool
    {
        $bill->loadMissing(['lines', 'purchaseOrder.lines']);

        foreach ($bill->lines as $billLine) {
            /** @var VendorBillLine $billLine */
            if (! $billLine->product_id) {
                continue; // Skip service lines
            }

            // Find matching PO line
            $poLine = $bill->purchaseOrder?->lines
                ->first(fn ($l) => $l->product_id === $billLine->product_id);

            if (! $poLine) {
                // Product on bill not found on PO - potential issue
                continue;
            }

            // Compare billed quantity to received quantity
            // Allow for small floating point differences
            if (abs($billLine->quantity - $poLine->quantity_received) > 0.001) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if bill prices match PO prices.
     *
     * Uses a configurable tolerance (default 5% variance allowed).
     */
    private function checkPriceMatch(VendorBill $bill, float $tolerancePercent = 5.0): bool
    {
        $bill->loadMissing(['lines', 'purchaseOrder.lines']);

        foreach ($bill->lines as $billLine) {
            /** @var VendorBillLine $billLine */
            if (! $billLine->product_id) {
                continue;
            }

            $poLine = $bill->purchaseOrder?->lines
                ->first(fn ($l) => $l->product_id === $billLine->product_id);

            if (! $poLine) {
                continue;
            }

            // Compare unit prices
            $billPrice = $billLine->unit_price->getAmount()->toFloat();
            $poPrice = $poLine->unit_price->getAmount()->toFloat();

            if ($poPrice == 0) {
                continue; // Avoid division by zero
            }

            $variance = abs($billPrice - $poPrice) / $poPrice * 100;

            if ($variance > $tolerancePercent) {
                return false;
            }
        }

        return true;
    }
}
