<?php

namespace Modules\Accounting\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\Accounting\Enums\Accounting\LockDateType;
use Modules\Accounting\Models\LockDate;

class LockDateObserver
{
    public function updating(LockDate $lockDate): void
    {
        if ($lockDate->lock_type === LockDateType::HardLock) {
            throw new \Modules\Foundation\Exceptions\UpdateNotAllowedException('A hard lock date cannot be modified.');
        }
    }

    public function deleting(LockDate $lockDate): void
    {
        if ($lockDate->lock_type === LockDateType::HardLock) {
            throw new \Modules\Foundation\Exceptions\UpdateNotAllowedException('A hard lock date cannot be removed.');
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
