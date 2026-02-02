<?php

namespace Kezi\Inventory\Services\Inventory;

use App\Models\User;
use Kezi\Inventory\Actions\GoodsReceipt\CreateGoodsReceiptFromPurchaseOrderAction;
use Kezi\Inventory\Actions\GoodsReceipt\ValidateGoodsReceiptAction;
use Kezi\Inventory\DataTransferObjects\ReceiveGoodsFromPurchaseOrderDTO;
use Kezi\Inventory\DataTransferObjects\ValidateGoodsReceiptDTO;
use Kezi\Inventory\Enums\Inventory\StockPickingState;
use Kezi\Inventory\Enums\Inventory\StockPickingType;
use Kezi\Inventory\Models\StockPicking;
use Kezi\Purchase\Models\PurchaseOrder;

/**
 * Service for orchestrating the Goods Receipt Note (GRN) workflow.
 *
 * This service provides the high-level interface for:
 * - Creating GRNs from Purchase Orders
 * - Validating GRNs (completing the receiving process)
 * - Cancelling GRNs
 * - Finding existing GRNs for a PO
 */
class GoodsReceiptService
{
    public function __construct(
        private readonly CreateGoodsReceiptFromPurchaseOrderAction $createAction,
        private readonly ValidateGoodsReceiptAction $validateAction,
    ) {}

    /**
     * Create a Goods Receipt from a Purchase Order.
     *
     * If a draft GRN already exists for this PO, returns that instead of creating a new one.
     */
    public function createFromPurchaseOrder(ReceiveGoodsFromPurchaseOrderDTO $dto): StockPicking
    {
        // Check if a draft GRN already exists for this PO
        $existingPicking = $this->findDraftGoodsReceiptForPurchaseOrder($dto->purchaseOrder);

        if ($existingPicking) {
            return $existingPicking;
        }

        return $this->createAction->execute($dto);
    }

    /**
     * Validate a Goods Receipt, completing the receiving process.
     */
    public function validate(ValidateGoodsReceiptDTO $dto): StockPicking
    {
        return $this->validateAction->execute($dto);
    }

    /**
     * Cancel a Goods Receipt.
     *
     * Only draft or confirmed pickings can be cancelled.
     */
    public function cancel(StockPicking $picking, User $user): void
    {
        if ($picking->isDone()) {
            throw new \InvalidArgumentException('Cannot cancel a validated goods receipt. Use reversal instead.');
        }

        if ($picking->isCancelled()) {
            return; // Already cancelled
        }

        $picking->update([
            'state' => StockPickingState::Cancelled,
        ]);

        // Cancel associated stock moves
        foreach ($picking->stockMoves as $move) {
            $move->update(['status' => \Kezi\Inventory\Enums\Inventory\StockMoveStatus::Cancelled]);
        }
    }

    /**
     * Find an existing draft GRN for a Purchase Order.
     */
    public function findDraftGoodsReceiptForPurchaseOrder(PurchaseOrder $purchaseOrder): ?StockPicking
    {
        return StockPicking::where('purchase_order_id', $purchaseOrder->id)
            ->where('type', StockPickingType::Receipt)
            ->whereIn('state', [StockPickingState::Draft, StockPickingState::Confirmed, StockPickingState::Assigned])
            ->first();
    }

    /**
     * Find all GRNs for a Purchase Order.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, StockPicking>
     */
    public function findAllGoodsReceiptsForPurchaseOrder(PurchaseOrder $purchaseOrder): \Illuminate\Database\Eloquent\Collection
    {
        return StockPicking::where('purchase_order_id', $purchaseOrder->id)
            ->where('type', StockPickingType::Receipt)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Check if a Purchase Order has any validated GRNs.
     */
    public function hasValidatedGoodsReceipt(PurchaseOrder $purchaseOrder): bool
    {
        return StockPicking::where('purchase_order_id', $purchaseOrder->id)
            ->where('type', StockPickingType::Receipt)
            ->where('state', StockPickingState::Done)
            ->exists();
    }

    /**
     * Get total received quantity for a specific PO line across all GRNs.
     */
    public function getTotalReceivedQuantityForLine(int $purchaseOrderLineId): float
    {
        return \Kezi\Inventory\Models\StockMoveProductLine::where('source_type', \Kezi\Purchase\Models\PurchaseOrderLine::class)
            ->where('source_id', $purchaseOrderLineId)
            ->whereHas('stockMove', function ($query) {
                $query->where('status', \Kezi\Inventory\Enums\Inventory\StockMoveStatus::Done);
            })
            ->sum('quantity');
    }
}
