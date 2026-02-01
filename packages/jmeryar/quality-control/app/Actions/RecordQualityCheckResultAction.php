<?php

namespace Jmeryar\QualityControl\Actions;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Jmeryar\QualityControl\DataTransferObjects\RecordQualityCheckResultDTO;
use Jmeryar\QualityControl\Enums\QualityCheckStatus;
use Jmeryar\QualityControl\Models\QualityCheck;
use Jmeryar\QualityControl\Models\QualityCheckLine;

class RecordQualityCheckResultAction
{
    public function execute(RecordQualityCheckResultDTO $dto): QualityCheck
    {
        return DB::transaction(function () use ($dto) {
            $qualityCheck = QualityCheck::with(['lines.parameter'])->findOrFail($dto->qualityCheckId);

            // Update each check line with results
            foreach ($dto->lineResults as $lineResult) {
                $line = QualityCheckLine::where('quality_check_id', $qualityCheck->id)
                    ->where('parameter_id', $lineResult['parameter_id'])
                    ->firstOrFail();

                $updateData = [];

                // Set result based on check type
                if (isset($lineResult['result_pass_fail'])) {
                    $updateData['result_pass_fail'] = $lineResult['result_pass_fail'];
                }

                if (isset($lineResult['result_numeric'])) {
                    $updateData['result_numeric'] = $lineResult['result_numeric'];
                    // Calculate if within tolerance
                    $updateData['is_within_tolerance'] = $line->parameter->isWithinTolerance($lineResult['result_numeric']);
                }

                if (isset($lineResult['result_text'])) {
                    $updateData['result_text'] = $lineResult['result_text'];
                }

                if (isset($lineResult['result_image_path'])) {
                    $updateData['result_image_path'] = $lineResult['result_image_path'];
                }

                $line->update($updateData);
            }

            // Determine overall status based on all lines
            $overallStatus = $this->determineOverallStatus($qualityCheck->fresh('lines'));

            // Update quality check
            $qualityCheck->update([
                'status' => $overallStatus,
                'inspected_by_user_id' => $dto->inspectedByUserId,
                'inspected_at' => Carbon::now(),
                'notes' => $dto->notes ?? $qualityCheck->notes,
            ]);

            return $qualityCheck->fresh(['lines.parameter', 'inspectedByUser']);
        });
    }

    private function determineOverallStatus(QualityCheck $qualityCheck): QualityCheckStatus
    {
        if ($qualityCheck->lines->isEmpty()) {
            return QualityCheckStatus::Draft;
        }

        $allPassed = $qualityCheck->areAllLinesPassed();

        if ($allPassed) {
            return QualityCheckStatus::Passed;
        }

        // If any critical failures, mark as failed
        // For now, any failure = failed check
        return QualityCheckStatus::Failed;
    }
}
