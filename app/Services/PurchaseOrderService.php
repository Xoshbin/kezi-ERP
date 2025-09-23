<?php

namespace App\Services;

use App\Enums\Purchases\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\Accounting\LockDateService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Service for managing Purchase Order operations
 */
class PurchaseOrderService
{
    public function __construct(
        protected LockDateService $lockDateService,
        protected SequenceService $sequenceService
    ) {}

    /**
     * Send RFQ to vendors
     */
    public function sendRFQ(PurchaseOrder $purchaseOrder, User $user): PurchaseOrder
    {
        if (!$purchaseOrder->status->canSendRFQ()) {
            throw new InvalidArgumentException('RFQ cannot be sent in the current state.');
        }

        if ($purchaseOrder->lines->isEmpty()) {
            throw new InvalidArgumentException('Cannot send RFQ without any lines.');
        }

        return DB::transaction(function () use ($purchaseOrder, $user) {
            $purchaseOrder->status = PurchaseOrderStatus::RFQSent;
            $purchaseOrder->save();

            return $purchaseOrder;
        });
    }

    /**
     * Send purchase order to vendor
     */
    public function send(PurchaseOrder $purchaseOrder, User $user): PurchaseOrder
    {
        if (!$purchaseOrder->status->canBeSent()) {
            throw new InvalidArgumentException('Purchase order cannot be sent in its current state.');
        }

        if ($purchaseOrder->lines->isEmpty()) {
            throw new InvalidArgumentException('Cannot send purchase order without any lines.');
        }

        $this->lockDateService->enforce($purchaseOrder->company, $purchaseOrder->po_date);

        return DB::transaction(function () use ($purchaseOrder, $user) {
            // Generate PO number if not already set
            if (!$purchaseOrder->po_number) {
                $purchaseOrder->po_number = $this->sequenceService->getNextNumber(
                    $purchaseOrder->company,
                    'purchase_order',
                    'PO'
                );
            }

            $purchaseOrder->status = PurchaseOrderStatus::Sent;
            $purchaseOrder->save();

            return $purchaseOrder;
        });
    }

    /**
     * Confirm a purchase order
     */
    public function confirm(PurchaseOrder $purchaseOrder, User $user): PurchaseOrder
    {
        if (!$purchaseOrder->canBeConfirmed()) {
            throw new InvalidArgumentException('Purchase order cannot be confirmed in its current state.');
        }

        if ($purchaseOrder->lines->isEmpty()) {
            throw new InvalidArgumentException('Cannot confirm purchase order without any lines.');
        }

        $this->lockDateService->enforce($purchaseOrder->company, $purchaseOrder->po_date);

        return DB::transaction(function () use ($purchaseOrder, $user) {
            // Generate PO number if not already set (in case it was confirmed directly from draft)
            if (!$purchaseOrder->po_number) {
                $purchaseOrder->po_number = $this->sequenceService->getNextNumber(
                    $purchaseOrder->company,
                    'purchase_order',
                    'PO'
                );
            }

            // Update status and confirmation timestamp
            $purchaseOrder->status = PurchaseOrderStatus::Confirmed;
            $purchaseOrder->confirmed_at = now();
            $purchaseOrder->save();

            return $purchaseOrder;
        });
    }

    /**
     * Cancel a purchase order
     */
    public function cancel(PurchaseOrder $purchaseOrder, User $user, ?string $reason = null): PurchaseOrder
    {
        if (!$purchaseOrder->canBeCancelled()) {
            throw new InvalidArgumentException('Purchase order cannot be cancelled in its current state.');
        }

        $this->lockDateService->enforce($purchaseOrder->company, $purchaseOrder->po_date);

        return DB::transaction(function () use ($purchaseOrder, $reason) {
            $purchaseOrder->status = PurchaseOrderStatus::Cancelled;
            $purchaseOrder->cancelled_at = now();

            if ($reason) {
                $purchaseOrder->notes = ($purchaseOrder->notes ? $purchaseOrder->notes . "\n\n" : '')
                    . "Cancelled: " . $reason;
            }

            $purchaseOrder->save();

            return $purchaseOrder;
        });
    }

    /**
     * Update received quantities for a purchase order line
     */
    public function updateReceivedQuantity(PurchaseOrder $purchaseOrder, int $lineId, float $receivedQuantity): PurchaseOrder
    {
        if (!$purchaseOrder->canReceiveGoods()) {
            throw new InvalidArgumentException('Cannot receive goods for this purchase order.');
        }

        return DB::transaction(function () use ($purchaseOrder, $lineId, $receivedQuantity) {
            $line = $purchaseOrder->lines()->findOrFail($lineId);
            $line->updateReceivedQuantity($receivedQuantity);
            $line->save();

            // Refresh the purchase order to get updated line quantities
            $purchaseOrder->refresh();
            $purchaseOrder->load('lines');

            // Update purchase order status based on received quantities
            $purchaseOrder->updateStatusBasedOnReceipts();
            $purchaseOrder->save();

            return $purchaseOrder->fresh(['lines']);
        });
    }

    /**
     * Get purchase orders that can be used for cost determination for a product
     */
    public function getAvailableForCostDetermination(int $productId, int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return PurchaseOrder::whereHas('lines', function ($query) use ($productId) {
            $query->where('product_id', $productId)
                ->where('quantity_received', '>', 0);
        })
            ->where('company_id', $companyId)
            ->whereIn('status', PurchaseOrderStatus::activeStatuses())
            ->with(['lines' => function ($query) use ($productId) {
                $query->where('product_id', $productId);
            }])
            ->orderByDesc('confirmed_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get the latest purchase order line for cost determination
     */
    public function getLatestLineForCostDetermination(int $productId, int $companyId): ?\App\Models\PurchaseOrderLine
    {
        return \App\Models\PurchaseOrderLine::whereHas('purchaseOrder', function ($query) use ($companyId) {
            $query->where('company_id', $companyId)
                ->whereIn('status', PurchaseOrderStatus::activeStatuses());
        })
            ->where('product_id', $productId)
            ->where('quantity_received', '>', 0)
            ->with(['purchaseOrder', 'tax'])
            ->join('purchase_orders', 'purchase_order_lines.purchase_order_id', '=', 'purchase_orders.id')
            ->orderByDesc('purchase_orders.confirmed_at')
            ->orderByDesc('purchase_orders.created_at')
            ->select('purchase_order_lines.*')
            ->first();
    }

    /**
     * Mark a purchase order as done/closed
     */
    public function markAsDone(PurchaseOrder $purchaseOrder, User $user): PurchaseOrder
    {
        if ($purchaseOrder->status !== PurchaseOrderStatus::FullyBilled) {
            throw new InvalidArgumentException('Purchase order must be fully billed before it can be marked as done.');
        }

        return DB::transaction(function () use ($purchaseOrder, $user) {
            $purchaseOrder->status = PurchaseOrderStatus::Done;
            $purchaseOrder->save();

            return $purchaseOrder;
        });
    }
}
