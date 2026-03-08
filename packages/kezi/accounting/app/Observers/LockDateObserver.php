<?php

namespace Kezi\Accounting\Observers;

use Illuminate\Support\Facades\Cache;
use Kezi\Accounting\Enums\Accounting\LockDateType;
use Kezi\Accounting\Models\LockDate;

class LockDateObserver
{
    public function updating(LockDate $lockDate): void
    {
        if ($lockDate->lock_type === LockDateType::HardLock) {
            throw new \Kezi\Foundation\Exceptions\UpdateNotAllowedException(__('accounting::exceptions.lock_date.cannot_modify_hard_lock'));
        }
    }

    public function deleting(LockDate $lockDate): void
    {
        if ($lockDate->lock_type === LockDateType::HardLock) {
            throw new \Kezi\Foundation\Exceptions\UpdateNotAllowedException(__('accounting::exceptions.lock_date.cannot_remove_hard_lock'));
        }
    }

    public function saved(LockDate $lockDate): void
    {
        $this->clearCache($lockDate);
    }

    public function deleted(LockDate $lockDate): void
    {
        $this->clearCache($lockDate);
    }

    private function clearCache(LockDate $lockDate): void
    {
        $typeValue = $lockDate->lock_type->value;
        Cache::forget("lock_date_{$lockDate->company_id}_{$typeValue}");
    }
}
