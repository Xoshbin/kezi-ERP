<?php

namespace Kezi\QualityControl\Services;

use App\Models\User;
use Kezi\QualityControl\Actions\CreateQualityCheckAction;
use Kezi\QualityControl\Actions\RecordQualityCheckResultAction;
use Kezi\QualityControl\DataTransferObjects\CreateQualityCheckDTO;
use Kezi\QualityControl\DataTransferObjects\RecordQualityCheckResultDTO;
use Kezi\QualityControl\Models\QualityCheck;
use Kezi\QualityControl\Models\QualityControlPoint;

class QualityCheckService
{
    public function __construct(
        private readonly CreateQualityCheckAction $createQualityCheckAction,
        private readonly RecordQualityCheckResultAction $recordQualityCheckResultAction,
    ) {}

    /**
     * Create a quality check from a control point
     */
    public function createFromControlPoint(
        QualityControlPoint $controlPoint,
        mixed $source,
        int $productId,
        ?int $lotId = null,
        ?int $serialNumberId = null,
    ): QualityCheck {
        $dto = new CreateQualityCheckDTO(
            companyId: $controlPoint->company_id,
            sourceType: get_class($source),
            sourceId: $source->id,
            productId: $productId,
            lotId: $lotId,
            serialNumberId: $serialNumberId,
            inspectionTemplateId: $controlPoint->inspection_template_id,
            isBlocking: $controlPoint->is_blocking,
        );

        return $this->createQualityCheckAction->execute($dto);
    }

    /**
     * Record inspection results
     */
    public function recordResults(
        QualityCheck $qualityCheck,
        array $lineResults,
        User $inspector,
        ?string $notes = null
    ): QualityCheck {
        $dto = new RecordQualityCheckResultDTO(
            qualityCheckId: $qualityCheck->id,
            inspectedByUserId: $inspector->id,
            lineResults: $lineResults,
            notes: $notes,
        );

        return $this->recordQualityCheckResultAction->execute($dto);
    }
}
