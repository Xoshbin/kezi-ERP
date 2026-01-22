<?php

namespace Modules\QualityControl\Listeners;

use Modules\Inventory\Enums\Inventory\TrackingType;
use Modules\Inventory\Events\StockPickingValidated;
use Modules\QualityControl\Enums\QualityTriggerOperation;
use Modules\QualityControl\Services\QualityCheckService;
use Modules\QualityControl\Services\QualityControlPointService;

class CreateQualityChecksForStockPicking
{
    public function __construct(
        private readonly QualityControlPointService $controlPointService,
        private readonly QualityCheckService $checkService,
    ) {}

    public function handle(StockPickingValidated $event): void
    {
        $picking = $event->stockPicking;

        // Determine trigger operation based on picking type
        $operation = match (true) {
            $picking->isGoodsReceipt() => QualityTriggerOperation::GoodsReceipt,
            $picking->isInternalTransfer() => QualityTriggerOperation::InternalTransfer,
            default => null,
        };

        if ($operation === null) {
            return;
        }

        // Aggregate product data across all moves for correct quantity triggering
        $productData = [];

        foreach ($picking->stockMoves as $stockMove) {
            foreach ($stockMove->productLines as $productLine) {
                /** @var \Modules\Product\Models\Product|null $product */
                $product = $productLine->product;

                if ($product === null) {
                    continue;
                }

                $productId = $product->id;
                if (! isset($productData[$productId])) {
                    $productData[$productId] = [
                        'product' => $product,
                        'total_quantity' => 0,
                        'lots' => [],
                        'serials' => [],
                    ];
                }

                $productData[$productId]['total_quantity'] += $productLine->quantity;

                foreach ($productLine->stockMoveLines as $moveLine) {
                    if ($moveLine->lot_id) {
                        $productData[$productId]['lots'][$moveLine->lot_id] = $moveLine->lot_id;
                    }
                    if ($moveLine->serial_number_id) {
                        $productData[$productId]['serials'][$moveLine->serial_number_id] = $moveLine->serial_number_id;
                    }
                }
            }
        }

        // For each product found, trigger control points
        foreach ($productData as $productId => $data) {
            /** @var \Modules\Product\Models\Product $product */
            $product = $data['product'];

            $controlPoints = $this->controlPointService->findTriggeredControlPoints(
                $operation,
                $product,
                $data['total_quantity']
            );

            foreach ($controlPoints as $controlPoint) {
                // Create quality check for each triggered control point
                // For each lot/serial if tracked
                if ($product->tracking_type === TrackingType::Lot) {
                    foreach ($data['lots'] as $lotId) {
                        $this->checkService->createFromControlPoint(
                            $controlPoint,
                            $picking,
                            $productId,
                            $lotId,
                            null
                        );
                    }
                } elseif ($product->tracking_type === TrackingType::Serial) {
                    foreach ($data['serials'] as $serialId) {
                        $this->checkService->createFromControlPoint(
                            $controlPoint,
                            $picking,
                            $productId,
                            null,
                            $serialId
                        );
                    }
                } else {
                    // No tracking - one check per product
                    $this->checkService->createFromControlPoint(
                        $controlPoint,
                        $picking,
                        $productId,
                        null,
                        null
                    );
                }
            }
        }
    }
}
