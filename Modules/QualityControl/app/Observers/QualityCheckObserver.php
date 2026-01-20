<?php

namespace Modules\QualityControl\Observers;

use Modules\QualityControl\Actions\RejectLotAction;
use Modules\QualityControl\DataTransferObjects\RejectLotDTO;
use Modules\QualityControl\Enums\QualityCheckStatus;
use Modules\QualityControl\Models\QualityCheck;

class QualityCheckObserver
{
    public function updated(QualityCheck $qualityCheck): void
    {
        if ($qualityCheck->isDirty('status') && $qualityCheck->status === QualityCheckStatus::Failed) {
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
