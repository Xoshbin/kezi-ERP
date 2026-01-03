<?php

namespace Modules\Accounting\Listeners;

use Illuminate\Support\Facades\Cache;
use Modules\Accounting\Enums\Accounting\LockDateType;
use Modules\Accounting\Events\FiscalPeriodClosed;
use Modules\Accounting\Models\LockDate;

class UpdateLockDateOnFiscalPeriodClose
{
    /**
     * Handle the event.
     *
     * Automatically updates the lock date to the fiscal period end date
     * when a fiscal period is closed.
     */
    public function handle(FiscalPeriodClosed $event): void
    {
        $fiscalPeriod = $event->fiscalPeriod;
        $companyId = $fiscalPeriod->fiscalYear->company_id;

        // Get current lock date
        $currentLock = LockDate::where('company_id', $companyId)
            ->where('lock_type', LockDateType::AllUsers->value)
            ->first();

        // Only update if this period's end date is AFTER the current lock date
        // This prevents issues when closing periods out of order
        if (! $currentLock || $fiscalPeriod->end_date->gt($currentLock->locked_until)) {
            LockDate::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'lock_type' => LockDateType::AllUsers->value,
                ],
                [
                    'locked_until' => $fiscalPeriod->end_date,
                ]
            );

            // Clear the lock date cache
            $cacheKey = "lock_date_{$companyId}_".LockDateType::AllUsers->value;
            Cache::forget($cacheKey);
        }
    }
}
