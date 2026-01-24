<?php

namespace Modules\QualityControl\Observers;

use App\Models\User;
use Modules\QualityControl\Actions\RejectLotAction;
use Modules\QualityControl\DataTransferObjects\RejectLotDTO;
use Modules\QualityControl\Enums\QualityCheckStatus;
use Modules\QualityControl\Models\QualityCheck;

class QualityCheckObserver
{
    public function updated(QualityCheck $qualityCheck): void
    {
        if ($qualityCheck->isDirty('status') && $qualityCheck->status === QualityCheckStatus::Failed) {
            // Auto-create Quality Alert for blocking checks
            if ($qualityCheck->is_blocking) {
                app(\Modules\QualityControl\Actions\CreateQualityAlertAction::class)->execute(
                    new \Modules\QualityControl\DataTransferObjects\CreateQualityAlertDTO(
                        companyId: $qualityCheck->company_id,
                        qualityCheckId: $qualityCheck->id,
                        productId: $qualityCheck->product_id,
                        lotId: $qualityCheck->lot_id,
                        serialNumberId: $qualityCheck->serial_number_id,
                        defectTypeId: null, // To be filled by user later
                        description: 'Quality Check Failed'.($qualityCheck->notes ? ': '.$qualityCheck->notes : ''),
                        reportedByUserId: $qualityCheck->inspected_by_user_id ?? auth()->id() ?? User::first()->id, // Fallback
                    )
                );
            }

            if ($qualityCheck->lot_id) {
                $rejectAction = app(RejectLotAction::class);

                $rejectAction->execute(new RejectLotDTO(
                    lotId: $qualityCheck->lot_id,
                    rejectionReason: $qualityCheck->notes ?? 'Quality Check Failed',
                    quarantineLocationId: null // Pending user configuration or default
                ));
            }
        }
    }
}
