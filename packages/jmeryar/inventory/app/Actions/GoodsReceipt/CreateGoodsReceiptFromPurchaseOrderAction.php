<?php

namespace Jmeryar\Inventory\Actions\GoodsReceipt;

use Illuminate\Support\Facades\DB;
use Jmeryar\Inventory\DataTransferObjects\ReceiveGoodsFromPurchaseOrderDTO;
use Jmeryar\Inventory\Enums\Inventory\StockLocationType;
use Jmeryar\Inventory\Enums\Inventory\StockMoveStatus;
use Jmeryar\Inventory\Enums\Inventory\StockMoveType;
use Jmeryar\Inventory\Enums\Inventory\StockPickingState;
use Jmeryar\Inventory\Enums\Inventory\StockPickingType;
use Jmeryar\Inventory\Models\StockLocation;
use Jmeryar\Inventory\Models\StockMove;
use Jmeryar\Inventory\Models\StockMoveProductLine;
use Jmeryar\Inventory\Models\StockPicking;
use Jmeryar\Product\Enums\Products\ProductType;

/**
 * Creates a Goods Receipt Note (StockPicking) from a confirmed Purchase Order.
 *
 * This action creates a draft StockPicking with associated StockMoves for all
 * storable products from the PO. The picking must be confirmed and validated
 * separately to complete the receiving process.
 */
class CreateGoodsReceiptFromPurchaseOrderAction
{
    /**
     * Execute the action to create a GRN from a Purchase Order.
     */
    public function execute(ReceiveGoodsFromPurchaseOrderDTO $dto): StockPicking
    {
        return DB::transaction(function () use ($dto) {
            $purchaseOrder = $dto->purchaseOrder;
            $company = $purchaseOrder->company;

            // Filter lines for storable products only
            $storableLines = $purchaseOrder->lines->filter(function ($line) {
                return $line->product && $line->product->type === ProductType::Storable;
            });

            if ($storableLines->isEmpty()) {
                throw new \InvalidArgumentException(
                    'Cannot create goods receipt: Purchase Order has no storable products.'
                );
            }

            // Get vendor location (source)
            $vendorLocation = StockLocation::firstOrCreate([
                'company_id' => $company->id,
                'type' => StockLocationType::Vendor,
            ], [
                'name' => 'Vendors',
                'is_active' => true,
            ]);

            // Get destination location
            $destinationLocation = $dto->location
                ?? $purchaseOrder->deliveryLocation
                ?? $company->defaultStockLocation
                ?? StockLocation::where('company_id', $company->id)
                    ->where('type', StockLocationType::Internal)
                    ->first();

            if (! $destinationLocation) {
                throw new \RuntimeException(
                    "No internal stock location configured for Company ID: {$company->id}"
                );
            }

            // Create the StockPicking (GRN)
            $picking = StockPicking::create([
                'company_id' => $company->id,
                'type' => StockPickingType::Receipt,
                'state' => StockPickingState::Draft,
                'partner_id' => $purchaseOrder->vendor_id,
                'purchase_order_id' => $purchaseOrder->id,
                'scheduled_date' => $dto->receiptDate ?? $purchaseOrder->expected_delivery_date ?? now(),
                'origin' => $purchaseOrder->po_number,
                'created_by_user_id' => $dto->userId,
            ]);

            // Create StockMoves for each storable line
            foreach ($storableLines as $line) {
                // Only create move for remaining quantity not yet received
                $remainingQuantity = $line->getRemainingQuantity();

                if ($remainingQuantity <= 0) {
                    continue; // Skip fully received lines
                }

                $move = StockMove::create([
                    'company_id' => $company->id,
                    'picking_id' => $picking->id,
                    'move_type' => StockMoveType::Incoming,
                    'status' => StockMoveStatus::Draft,
                    'move_date' => $dto->receiptDate ?? now(),
                    'created_by_user_id' => $dto->userId,
                    'reference' => $line->description,
                    'description' => "Receive {$purchaseOrder->po_number}",
                    'source_type' => get_class($line),
                    'source_id' => $line->id,
                ]);

                // Create the product line for this move
                StockMoveProductLine::create([
                    'company_id' => $company->id,
                    'stock_move_id' => $move->id,
                    'product_id' => $line->product_id,
                    'quantity' => $remainingQuantity,
                    'from_location_id' => $vendorLocation->id,
                    'to_location_id' => $destinationLocation->id,
                    'source_type' => get_class($line),
                    'source_id' => $line->id,
                ]);
            }

            return $picking->fresh(['stockMoves.productLines']);
        });
    }
}
