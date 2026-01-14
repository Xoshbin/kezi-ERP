<?php

namespace Modules\QualityControl\Actions;

use Illuminate\Support\Facades\DB;
use Modules\QualityControl\DataTransferObjects\CreateQualityAlertDTO;
use Modules\QualityControl\Enums\QualityAlertStatus;
use Modules\QualityControl\Models\QualityAlert;

class CreateQualityAlertAction
{
    public function execute(CreateQualityAlertDTO $dto): QualityAlert
    {
        return DB::transaction(function () use ($dto) {
            // Generate unique number
            $number = $this->generateQualityAlertNumber($dto->companyId);

            // Create the quality alert
            $qualityAlert = QualityAlert::create([
                'company_id' => $dto->companyId,
                'number' => $number,
                'quality_check_id' => $dto->qualityCheckId,
                'product_id' => $dto->productId,
                'lot_id' => $dto->lotId,
                'serial_number_id' => $dto->serialNumberId,
                'defect_type_id' => $dto->defectTypeId,
                'status' => QualityAlertStatus::New,
                'description' => $dto->description,
                'reported_by_user_id' => $dto->reportedByUserId,
                'assigned_to_user_id' => $dto->assignedToUserId,
            ]);

            return $qualityAlert->fresh([
                'qualityCheck',
                'product',
                'lot',
                'serialNumber',
                'defectType',
                'reportedByUser',
                'assignedToUser',
            ]);
        });
    }

    private function generateQualityAlertNumber(int $companyId): string
    {
        $lastAlert = QualityAlert::where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastAlert ? ((int) substr($lastAlert->number, 3)) + 1 : 1;

        return 'QA-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
