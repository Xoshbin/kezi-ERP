<?php

namespace App\Observers;

use App\Enums\Accounting\LockDateType;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\LockDate;
use Illuminate\Support\Facades\Cache;

class LockDateObserver
{
    public function updating(LockDate $lockDate): void
    {
        if ($lockDate->lock_type === LockDateType::HardLock) {
            throw new UpdateNotAllowedException('A hard lock date cannot be modified.');
        }
    }

    public function deleting(LockDate $lockDate): void
    {
        if ($lockDate->lock_type === LockDateType::HardLock) {
            throw new UpdateNotAllowedException('A hard lock date cannot be removed.');
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
