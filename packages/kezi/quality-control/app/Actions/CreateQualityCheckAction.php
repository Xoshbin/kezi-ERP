<?php

namespace Kezi\QualityControl\Actions;

use Illuminate\Support\Facades\DB;
use Kezi\QualityControl\DataTransferObjects\CreateQualityCheckDTO;
use Kezi\QualityControl\Enums\QualityCheckStatus;
use Kezi\QualityControl\Models\QualityCheck;
use Kezi\QualityControl\Models\QualityCheckLine;
use Kezi\QualityControl\Models\QualityInspectionTemplate;

class CreateQualityCheckAction
{
    public function execute(CreateQualityCheckDTO $dto): QualityCheck
    {
        return DB::transaction(function () use ($dto) {
            // Generate unique number
            $number = $this->generateQualityCheckNumber($dto->companyId);

            // Create the quality check
            $qualityCheck = QualityCheck::create([
                'company_id' => $dto->companyId,
                'number' => $number,
                'source_type' => $dto->sourceType,
                'source_id' => $dto->sourceId,
                'product_id' => $dto->productId,
                'lot_id' => $dto->lotId,
                'serial_number_id' => $dto->serialNumberId,
                'inspection_template_id' => $dto->inspectionTemplateId,
                'status' => QualityCheckStatus::Draft,
                'notes' => $dto->notes,
                'is_blocking' => $dto->isBlocking,
            ]);

            // If template is provided, create check lines from template parameters
            if ($dto->inspectionTemplateId !== null) {
                $template = QualityInspectionTemplate::with('parameters')->find($dto->inspectionTemplateId);

                foreach ($template->parameters as $parameter) {
                    QualityCheckLine::create([
                        'quality_check_id' => $qualityCheck->id,
                        'parameter_id' => $parameter->id,
                    ]);
                }
            }

            return $qualityCheck->fresh(['lines.parameter']);
        });
    }

    private function generateQualityCheckNumber(int $companyId): string
    {
        $lastCheck = QualityCheck::where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastCheck ? ((int) substr($lastCheck->number, 3)) + 1 : 1;

        return 'QC-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
