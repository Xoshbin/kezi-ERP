<?php

namespace Kezi\Inventory\Actions\Inventory;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Services\Inventory\InventoryValuationService;
use RuntimeException;

class CreateJournalEntryForStockMoveAction
{
    public function __construct(
        protected InventoryValuationService $inventoryValuationService,
    ) {}

    public function execute(StockMove $stockMove, User $user): void
    {
        DB::transaction(function () use ($stockMove, $user) {
            $stockMove->load('productLines.product');

            foreach ($stockMove->productLines as $productLine) {
                $product = $productLine->product;

                if (! $product) {
                    throw new RuntimeException("Product not found for product line ID {$productLine->id}");
                }

                // Create journal entries based on move type
                switch ($stockMove->move_type) {
                    case StockMoveType::Incoming:
                        $this->processIncomingMove($stockMove, $productLine, $user);
                        break;

                    case StockMoveType::Outgoing:
                        $this->processOutgoingMove($stockMove, $productLine, $user);
                        break;

                    case StockMoveType::InternalTransfer:
                        // Internal transfers don't affect valuation, only location
                        // No journal entry needed for pure location transfers
                        break;

                    case StockMoveType::Adjustment:
                        // Adjustments are handled by CreateInventoryAdjustmentAction
                        // This should not be called for adjustments
                        break;
                }
            }
        });
    }

    protected function processIncomingMove(StockMove $stockMove, $productLine, User $user): void
    {
        $product = $productLine->product;

        // Determine cost per unit using enhanced valuation rules (includes cost source tracking)
        $costResult = $this->inventoryValuationService->calculateIncomingCostPerUnitEnhanced($product, $stockMove, false);

        // Use the existing inventory valuation service for incoming stock
        $this->inventoryValuationService->processIncomingStock(
            $product,
            $productLine->quantity,
            $costResult->cost,
            $stockMove->move_date,
            $stockMove // Use the stock move as the source document
        );
    }

    protected function processOutgoingMove(StockMove $stockMove, $productLine, User $user): void
    {
        $product = $productLine->product;

        // Use the existing inventory valuation service for outgoing stock
        $this->inventoryValuationService->processOutgoingStock(
            $product,
            $productLine->quantity,
            $stockMove->move_date,
            $stockMove // Use the stock move as the source document
        );
    }
}
