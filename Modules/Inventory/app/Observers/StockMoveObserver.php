<?php

namespace Modules\Inventory\Observers;

use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Models\JournalEntry;
use Modules\Foundation\Models\AuditLog;
use Modules\Inventory\Actions\Inventory\CreateJournalEntryForStockMoveAction;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Services\Inventory\InventoryValuationService;
use Modules\Inventory\Services\Inventory\StockQuantService;
use Modules\Purchase\Models\VendorBill;

class StockMoveObserver
{
    public function creating(StockMove $stockMove): void
    {
        if (empty($stockMove->reference)) {
            $stockMove->reference = 'SM-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        }
    }

    public function created(StockMove $stockMove): void
    {
        $this->logAction('created', $stockMove);
    }

    public function updated(StockMove $stockMove): void
    {
        // Check if the status was just changed to 'Done'
        if ($stockMove->wasChanged('status') && $stockMove->status === StockMoveStatus::Done) {
            // Skip auto-created moves linked to Vendor Bills (handled via ProcessIncomingStockAction)
            if ($stockMove->source_type === VendorBill::class) {
                return;
            }

            // Ensure a journal entry doesn't already exist to prevent duplicates
            if ($stockMove->stockMoveValuations()->doesntExist()) {
                $user = Auth::user();
                if ($user) {
                    // Check if this is a truly manual stock move (not linked to any source document)
                    if (! $stockMove->source_type || ! $stockMove->source_id) {
                        // Use consolidated approach for manual stock moves to create a single journal entry
                        $inventoryValuationService = app(InventoryValuationService::class);
                        $inventoryValuationService->createConsolidatedManualStockMoveJournalEntry($stockMove);
                    } elseif ($stockMove->source_type === VendorBill::class) {
                        // For stock moves linked to vendor bills, use consolidated approach
                        $inventoryValuationService = app(InventoryValuationService::class);

                        // Check if a consolidated journal entry already exists for this vendor bill
                        $existingJournalEntry = JournalEntry::where('source_type', VendorBill::class)
                            ->where('source_id', $stockMove->source_id)
                            ->where('reference', 'LIKE', 'STOCK-IN-%')
                            ->first();

                        if (! $existingJournalEntry) {
                            // Get all stock moves for the same vendor bill
                            $allStockMoves = StockMove::where('source_type', VendorBill::class)
                                ->where('source_id', $stockMove->source_id)
                                ->where('status', StockMoveStatus::Done)
                                ->get();

                            // Use the existing consolidated method for vendor bill stock moves
                            $vendorBill = $stockMove->source;
                            $inventoryValuationService->createConsolidatedIncomingStockJournalEntry($allStockMoves->toArray(), $vendorBill);
                        }
                    } else {
                        // Use individual processing for other source document types
                        $createJournalEntryAction = app(CreateJournalEntryForStockMoveAction::class);
                        $createJournalEntryAction->execute($stockMove, $user);
                    }
                }
            }

            // Update stock quants for the movement
            $stockQuantService = app(StockQuantService::class);

            if ($stockMove->move_type === StockMoveType::Incoming) {
                // For incoming moves, update stock quants for each product line
                foreach ($stockMove->productLines as $productLine) {
                    $stockQuantService->applyForIncomingProductLine($productLine);
                }

                // Update Purchase Order status if this stock move is related to a PO
                $this->updatePurchaseOrderStatusFromStockMove($stockMove);
            } elseif ($stockMove->move_type === StockMoveType::Outgoing) {
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
        if ($stockMove->source_type === PurchaseOrder::class && $stockMove->source_id) {
            $purchaseOrder = PurchaseOrder::find($stockMove->source_id);
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
    protected function updatePurchaseOrderLineQuantities(PurchaseOrder $purchaseOrder, StockMove $stockMove): void
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
