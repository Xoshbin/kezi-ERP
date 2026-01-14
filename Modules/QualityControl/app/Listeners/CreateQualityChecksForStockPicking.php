<?php

namespace Modules\QualityControl\Listeners;

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

        // For each stock move in the picking, check if quality control points exist
        foreach ($picking->stockMoves as $stockMove) {
            $product = $stockMove->product;

            if (! $product) {
                continue;
            }

            $controlPoints = $this->controlPointService->findTriggeredControlPoints(
                $operation,
                $product,
                $stockMove->productLines->sum('quantity')
            );

            foreach ($controlPoints as $controlPoint) {
                // Create quality check for each triggered control point
                // For each lot/serial if tracked
                if ($product->tracking_type === 'lot') {
                    foreach ($stockMove->productLines as $productLine) {
                        if ($productLine->lot_id) {
                            $this->checkService->createFromControlPoint(
                                $controlPoint,
                                $picking,
                                $product->id,
                                $productLine->lot_id,
                                null
                            );
                        }
                    }
                } elseif ($product->tracking_type === 'serial') {
                    foreach ($stockMove->productLines as $productLine) {
                        if ($productLine->serial_number_id) {
                            $this->checkService->createFromControlPoint(
                                $controlPoint,
                                $picking,
                                $product->id,
                                null,
                                $productLine->serial_number_id
                            );
                        }
                    }
                } else {
                    // No tracking - one check per product
                    $this->checkService->createFromControlPoint(
                        $controlPoint,
                        $picking,
                        $product->id,
                        null,
                        null
                    );
                }
            }
        }
    }
}
