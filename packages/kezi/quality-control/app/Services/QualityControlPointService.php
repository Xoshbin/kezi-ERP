<?php

namespace Kezi\QualityControl\Services;

use Illuminate\Support\Collection;
use Kezi\Product\Models\Product;
use Kezi\QualityControl\Enums\QualityTriggerOperation;
use Kezi\QualityControl\Models\QualityControlPoint;

class QualityControlPointService
{
    /**
     * Find triggered control points for a given operation and product
     */
    public function findTriggeredControlPoints(
        QualityTriggerOperation $operation,
        Product $product,
        float $quantity = 1.0
    ): Collection {
        return QualityControlPoint::where('company_id', $product->company_id)
            ->where('trigger_operation', $operation)
            ->where('active', true)
            ->get()
            ->filter(function (QualityControlPoint $qcp) use ($product, $quantity) {
                // Check if control point applies to this product
                if (! $qcp->appliesToProduct($product->id)) {
                    return false;
                }

                // Check if it should trigger for this quantity
                return $qcp->shouldTriggerForQuantity($quantity);
            });
    }

    /**
     * Check if any blocking control points exist for the operation
     */
    public function hasBlockingControlPoints(
        QualityTriggerOperation $operation,
        Product $product,
        float $quantity = 1.0
    ): bool {
        $triggeredPoints = $this->findTriggeredControlPoints($operation, $product, $quantity);

        return $triggeredPoints->contains('is_blocking', true);
    }
}
