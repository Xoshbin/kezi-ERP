<?php

namespace Modules\Inventory\Actions\GoodsReceipt;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\DataTransferObjects\ValidateGoodsReceiptDTO;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockPickingState;
use Modules\Inventory\Enums\Inventory\StockPickingType;
use Modules\Inventory\Events\GoodsReceiptValidated;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Models\StockMoveProductLine;
use Modules\Inventory\Models\StockPicking;
use Modules\Inventory\Services\Inventory\StockQuantService;
use Modules\Purchase\Models\PurchaseOrderLine;

/**
 * Validates a Goods Receipt (StockPicking) and completes the receiving process.
 *
 * This action:
 * - Updates StockMoves and product lines with actual received quantities
 * - Creates StockQuant entries for received goods
 * - Optionally creates a backorder for partial receipts
 * - Dispatches GoodsReceiptValidated event for downstream processing
 */
class ValidateGoodsReceiptAction
{
    public function __construct(
        private readonly StockQuantService $stockQuantService,
    ) {}

    /**
     * Execute the validation of a goods receipt.
     */
    public function execute(ValidateGoodsReceiptDTO $dto): StockPicking
    {
        return DB::transaction(function () use ($dto) {
            $picking = $dto->stockPicking;

            // Validate picking can be validated
            if (! $picking->isGoodsReceipt()) {
                throw new \InvalidArgumentException('Only receipt pickings can be validated as goods receipts.');
            }

            if ($picking->isDone() || $picking->isCancelled()) {
                throw new \InvalidArgumentException('Cannot validate a picking that is already done or cancelled.');
            }

            $receivedLines = [];
            $backorderItems = [];

            // Process each stock move and product line
            foreach ($picking->stockMoves as $move) {
                foreach ($move->productLines as $productLine) {
                    // Find the corresponding line DTO if provided
                    $lineDto = $this->findLineDtoForProductLine($dto->lines, $productLine);

                    $plannedQuantity = $productLine->quantity;
                    $actualQuantity = $lineDto ? $lineDto->quantityToReceive : $plannedQuantity;

                    // Track backorder if partial receipt
                    if ($actualQuantity < $plannedQuantity && $dto->createBackorder) {
                        $backorderItems[] = [
                            'move' => $move,
                            'product_line' => $productLine,
                            'backorder_quantity' => $plannedQuantity - $actualQuantity,
                        ];
                    }

                    // Update product line with actual quantity
                    $productLine->update(['quantity' => $actualQuantity]);

                    // Track received lines for event dispatch
                    if ($actualQuantity > 0) {
                        $receivedLines[] = [
                            'product_id' => $productLine->product_id,
                            'quantity' => $actualQuantity,
                            'lot_id' => $lineDto?->lotId,
                            'purchase_order_line_id' => $productLine->source_id,
                        ];

                        // Update PurchaseOrderLine.quantity_received if linked
                        if ($productLine->source_type === PurchaseOrderLine::class && $productLine->source_id) {
                            $poLine = PurchaseOrderLine::find($productLine->source_id);
                            $poLine?->updateReceivedQuantity($actualQuantity);
                            $poLine?->save();
                        }
                    }
                }

                // Mark move as done
                // Note: StockMoveObserver handles inventory updates when status changes to Done
                $move->update(['status' => StockMoveStatus::Done]);
            }

            // Create backorder if needed
            $backorderPicking = null;
            if (! empty($backorderItems)) {
                $backorderPicking = $this->createBackorder($picking, $backorderItems, $dto->userId);
            }

            // Generate GRN number
            $grnNumber = $this->generateGrnNumber($picking);

            // Mark picking as done
            $picking->update([
                'state' => StockPickingState::Done,
                'completed_at' => now(),
                'validated_at' => now(),
                'validated_by_user_id' => $dto->userId,
                'grn_number' => $grnNumber,
            ]);

            // Dispatch event for downstream processing
            $user = \App\Models\User::find($dto->userId);
            if ($user) {
                GoodsReceiptValidated::dispatch($picking, $user, $receivedLines);
            }

            return $picking->fresh(['stockMoves.productLines', 'purchaseOrder']);
        });
    }

    /**
     * Find the DTO for a specific product line.
     *
     * @param  array<\Modules\Inventory\DataTransferObjects\ReceiveGoodsLineDTO>  $lines
     */
    private function findLineDtoForProductLine(array $lines, StockMoveProductLine $productLine): ?\Modules\Inventory\DataTransferObjects\ReceiveGoodsLineDTO
    {
        foreach ($lines as $lineDto) {
            if ($lineDto->purchaseOrderLineId === $productLine->source_id) {
                return $lineDto;
            }
        }

        return null;
    }

    /**
     * Create a backorder picking for partial receipts.
     *
     * @param  array<array{move: StockMove, product_line: StockMoveProductLine, backorder_quantity: float}>  $backorderItems
     */
    private function createBackorder(StockPicking $originalPicking, array $backorderItems, int $userId): StockPicking
    {
        $backorderPicking = StockPicking::create([
            'company_id' => $originalPicking->company_id,
            'type' => StockPickingType::Receipt,
            'state' => StockPickingState::Assigned,
            'partner_id' => $originalPicking->partner_id,
            'purchase_order_id' => $originalPicking->purchase_order_id,
            'scheduled_date' => now(),
            'origin' => ($originalPicking->reference ?? $originalPicking->origin).' (Backorder)',
            'reference' => ($originalPicking->reference ?? 'GRN').'-BO-'.rand(100, 999),
            'created_by_user_id' => $userId,
        ]);

        $moveCache = [];

        foreach ($backorderItems as $item) {
            /** @var StockMove $originalMove */
            $originalMove = $item['move'];
            /** @var StockMoveProductLine $originalLine */
            $originalLine = $item['product_line'];

            // Reuse or create backorder move
            if (! isset($moveCache[$originalMove->id])) {
                $newMove = $originalMove->replicate();
                $newMove->picking_id = $backorderPicking->id;
                $newMove->status = StockMoveStatus::Draft;
                $newMove->save();
                $moveCache[$originalMove->id] = $newMove;
            }

            $newMove = $moveCache[$originalMove->id];

            // Create backorder product line
            $newLine = $originalLine->replicate();
            $newLine->stock_move_id = $newMove->id;
            $newLine->quantity = $item['backorder_quantity'];
            $newLine->save();
        }

        return $backorderPicking;
    }

    /**
     * Generate a unique GRN number for the picking.
     */
    private function generateGrnNumber(StockPicking $picking): string
    {
        // Format: GRN-YYYYMMDD-XXXX
        $date = now()->format('Ymd');
        $sequence = StockPicking::where('company_id', $picking->company_id)
            ->whereNotNull('grn_number')
            ->whereYear('created_at', now()->year)
            ->count() + 1;

        return sprintf('GRN-%s-%04d', $date, $sequence);
    }
}
