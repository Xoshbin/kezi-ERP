<?php

declare(strict_types=1);

namespace Jmeryar\Accounting\Listeners;

use Illuminate\Support\Facades\Cache;
use Jmeryar\Accounting\Enums\Accounting\LockDateType;
use Jmeryar\Accounting\Events\FiscalPeriodClosed;
use Jmeryar\Accounting\Models\LockDate;

/**
 * Updates the company lock date when a fiscal period is closed.
 *
 * This listener ensures the lock date advances (but never regresses)
 * when periods are closed, preventing modifications to past transactions.
 */
final class UpdateLockDateOnFiscalPeriodClose
{
    /**
     * Handle the FiscalPeriodClosed event.
     */
    public function handle(FiscalPeriodClosed $event): void
    {
        $fiscalPeriod = $event->fiscalPeriod;
        $companyId = $fiscalPeriod->fiscalYear->company_id;

        if ($this->shouldUpdateLockDate($companyId, $fiscalPeriod->end_date)) {
            $this->updateLockDate($companyId, $fiscalPeriod->end_date);
            $this->clearCache($companyId);
        }
    }

    /**
     * Determine if the lock date should be updated.
     *
     * Only updates if the period's end date is after the current lock date.
     * This prevents issues when closing periods out of order.
     */
    private function shouldUpdateLockDate(int $companyId, \Carbon\Carbon $periodEndDate): bool
    {
        $currentLock = LockDate::query()
            ->where('company_id', $companyId)
            ->where('lock_type', LockDateType::AllUsers->value)
            ->first();

        return ! $currentLock || $periodEndDate->gt($currentLock->locked_until);
    }

    /**
     * Update or create the lock date record.
     */
    private function updateLockDate(int $companyId, \Carbon\Carbon $lockedUntil): void
    {
        LockDate::updateOrCreate(
            [
                'company_id' => $companyId,
                'lock_type' => LockDateType::AllUsers->value,
            ],
            [
                'locked_until' => $lockedUntil,
            ]
        );
    }

    /**
     * Clear the cached lock date for the company.
     */
    private function clearCache(int $companyId): void
    {
        $cacheKey = "lock_date_{$companyId}_".LockDateType::AllUsers->value;
        Cache::forget($cacheKey);
    }
}
