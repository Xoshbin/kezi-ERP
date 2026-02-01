<?php

namespace Jmeryar\Accounting\Listeners;

use Illuminate\Support\Facades\Cache;
use Jmeryar\Accounting\Enums\Accounting\LockDateType;
use Jmeryar\Accounting\Events\FiscalYearClosed;
use Jmeryar\Accounting\Models\LockDate;

class UpdateLockDateOnFiscalYearClose
{
    /**
     * Handle the event.
     *
     * Automatically updates the lock date to the fiscal year end date
     * when a fiscal year is closed.
     */
    public function handle(FiscalYearClosed $event): void
    {
        $fiscalYear = $event->fiscalYear;

        // Update or create the hard lock date
        LockDate::updateOrCreate(
            [
                'company_id' => $fiscalYear->company_id,
                'lock_type' => LockDateType::HardLock->value,
            ],
            [
                'locked_until' => $fiscalYear->end_date,
            ]
        );

        // Clear the lock date cache
        $cacheKey = "lock_date_{$fiscalYear->company_id}_".LockDateType::HardLock->value;
        Cache::forget($cacheKey);
    }
}
