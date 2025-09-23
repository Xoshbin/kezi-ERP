<?php

namespace App\Observers;

use App\Actions\Inventory\CreateJournalEntryForStockMoveAction;
use App\Enums\Inventory\StockMoveStatus;
use App\Models\AuditLog;
use App\Models\StockMove;
use Illuminate\Support\Facades\Auth;

class StockMoveObserver
{
    public function created(StockMove $stockMove): void
    {
        $this->logAction('created', $stockMove);
    }

    public function updated(StockMove $stockMove): void
    {
        // Check if the status was just changed to 'Done'
        if ($stockMove->wasChanged('status') && $stockMove->status === StockMoveStatus::Done) {
            // Skip auto-created moves linked to Vendor Bills (handled via consolidated JE)
            if ($stockMove->source_type === \App\Models\VendorBill::class) {
                return;
            }

            // Ensure a journal entry doesn't already exist to prevent duplicates
            if ($stockMove->stockMoveValuations()->doesntExist()) {
                $user = Auth::user();
                if ($user) {
                    app(CreateJournalEntryForStockMoveAction::class)->execute($stockMove, $user);
                }
            }

            // Update stock quants for the movement
            $stockQuantService = app(\App\Services\Inventory\StockQuantService::class);

            if ($stockMove->move_type === \App\Enums\Inventory\StockMoveType::Incoming) {
                // For incoming moves, update stock quants for each product line
                foreach ($stockMove->productLines as $productLine) {
                    $stockQuantService->applyForIncomingProductLine($productLine);
                }

                // Update Purchase Order status if this stock move is related to a PO
                $this->updatePurchaseOrderStatusFromStockMove($stockMove);
            } elseif ($stockMove->move_type === \App\Enums\Inventory\StockMoveType::Outgoing) {
                // For outgoing moves, stock consumption is handled by the reservation system
                // via StockMoveConfirmed event → ProcessOutgoingStockJob → ProcessOutgoingStockAction
                // Do not consume stock here to avoid double consumption
            }
        }

        $this->logAction('updated', $stockMove, $stockMove->getDirty());
    }

    public function deleted(StockMove $stockMove): void
    {
        $this->logAction('deleted', $stockMove);
    }

    /**
     * Update Purchase Order status when stock moves are completed.
     * This ensures that PO receive statuses are only updated through inventory operations.
     */
    protected function updatePurchaseOrderStatusFromStockMove(StockMove $stockMove): void
    {
        // Check if this stock move is related to a Purchase Order
        if ($stockMove->source_type === \App\Models\PurchaseOrder::class && $stockMove->source_id) {
            $purchaseOrder = \App\Models\PurchaseOrder::find($stockMove->source_id);
            if ($purchaseOrder) {
                // Update received quantities on PO lines based on stock move
                $this->updatePurchaseOrderLineQuantities($purchaseOrder, $stockMove);

                // Update PO status based on received quantities (from inventory operation)
                $purchaseOrder->updateStatusBasedOnReceipts(fromInventoryOperation: true);
                $purchaseOrder->save();
            }
        }
    }

    /**
     * Update Purchase Order line quantities based on completed stock move.
     */
    protected function updatePurchaseOrderLineQuantities(\App\Models\PurchaseOrder $purchaseOrder, StockMove $stockMove): void
    {
        foreach ($stockMove->productLines as $stockMoveProductLine) {
            // Find the corresponding PO line for this product
            $poLine = $purchaseOrder->lines()
                ->where('product_id', $stockMoveProductLine->product_id)
                ->first();

            if ($poLine) {
                // Add the received quantity from this stock move
                $poLine->quantity_received += $stockMoveProductLine->quantity;
                $poLine->save();
            }
        }
    }

    /**
     * @param  array<string, mixed>|null  $dirty
     */
    protected function logAction(string $action, StockMove $stockMove, ?array $dirty = null): void
    {
        $user = Auth::user();

        // Skip audit logging if no authenticated user (e.g., in console/tinker context)
        if (! $user) {
            return;
        }

        AuditLog::create([
            'user_id' => $user->id,
            'auditable_id' => $stockMove->id,
            'auditable_type' => StockMove::class,
            'event_type' => $action,
            'old_values' => $action === 'updated' ? $stockMove->getOriginal() : null,
            'new_values' => $action !== 'deleted' ? ($dirty ?? $stockMove->toArray()) : null,
            'description' => "Stock move {$action}: {$stockMove->id}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
