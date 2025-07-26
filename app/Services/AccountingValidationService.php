<?php

namespace App\Services;

use App\Exceptions\PeriodIsLockedException;
use App\Models\LockDate;
use Carbon\Carbon;

class AccountingValidationService
{
    /**
     * Checks if a given date for a company falls within a locked period.
     *
     * @throws PeriodIsLockedException
     */
    public function checkIfPeriodIsLocked(int $companyId, string $date): void
    {
        $entryDate = Carbon::parse($date);

        $lockDate = LockDate::where('company_id', $companyId)->first();

        if ($lockDate && $entryDate->lte($lockDate->locked_until)) {
            throw new PeriodIsLockedException('The accounting period is locked and cannot be modified.');
        }
    }
}