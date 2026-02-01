<?php

namespace Jmeryar\QualityControl\Observers;

use Illuminate\Validation\ValidationException;
use Jmeryar\QualityControl\Enums\QualityAlertStatus;
use Jmeryar\QualityControl\Models\QualityAlert;

class QualityAlertObserver
{
    public function updating(QualityAlert $qualityAlert): void
    {
        if ($qualityAlert->isDirty('status')) {
            $newStatus = $qualityAlert->status;

            if (in_array($newStatus, [QualityAlertStatus::Resolved, QualityAlertStatus::Closed])) {
                if (empty($qualityAlert->root_cause) || empty($qualityAlert->corrective_action) || empty($qualityAlert->preventive_action)) {
                    throw ValidationException::withMessages([
                        'status' => 'Root cause, corrective action, and preventive action are required to resolve or close the alert.',
                    ]);
                }
            }
        }
    }
}
