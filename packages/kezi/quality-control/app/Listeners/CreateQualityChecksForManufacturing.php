<?php

namespace Kezi\QualityControl\Listeners;

use Kezi\Manufacturing\Events\ManufacturingOrderConfirmed;
use Kezi\QualityControl\Enums\QualityTriggerOperation;
use Kezi\QualityControl\Services\QualityCheckService;
use Kezi\QualityControl\Services\QualityControlPointService;

class CreateQualityChecksForManufacturing
{
    public function __construct(
        private readonly QualityControlPointService $controlPointService,
        private readonly QualityCheckService $checkService,
    ) {}

    public function handle(ManufacturingOrderConfirmed $event): void
    {
        $mo = $event->manufacturingOrder;
        $product = $mo->product;

        if (! $product) {
            return;
        }

        $controlPoints = $this->controlPointService->findTriggeredControlPoints(
            QualityTriggerOperation::ManufacturingOutput,
            $product,
            $mo->quantity_to_produce
        );

        foreach ($controlPoints as $controlPoint) {
            // Create quality check for manufacturing output
            $this->checkService->createFromControlPoint(
                $controlPoint,
                $mo,
                $product->id,
                null, // Lot would be assigned during MO completion if tracked
                null
            );
        }
    }
}
