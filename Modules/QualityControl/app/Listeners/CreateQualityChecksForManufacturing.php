<?php

namespace Modules\QualityControl\Listeners;

use Modules\Manufacturing\Events\ManufacturingOrderCompleted;
use Modules\QualityControl\Enums\QualityTriggerOperation;
use Modules\QualityControl\Services\QualityCheckService;
use Modules\QualityControl\Services\QualityControlPointService;

class CreateQualityChecksForManufacturing
{
    public function __construct(
        private readonly QualityControlPointService $controlPointService,
        private readonly QualityCheckService $checkService,
    ) {}

    public function handle(ManufacturingOrderCompleted $event): void
    {
        $mo = $event->manufacturingOrder;
        $product = $mo->product;

        if (! $product) {
            return;
        }

        $controlPoints = $this->controlPointService->findTriggeredControlPoints(
            QualityTriggerOperation::ManufacturingOutput,
            $product,
            $mo->quantity_produced
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
