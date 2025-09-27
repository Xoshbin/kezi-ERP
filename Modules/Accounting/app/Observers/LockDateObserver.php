<?php

namespace Modules\Accounting\Observers;

use App\Enums\Accounting\LockDateType;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\LockDate;
use Illuminate\Support\Facades\Cache;

class LockDateObserver
{
    public function updating(\Modules\Accounting\Models\LockDate $lockDate): void
    {
        if ($lockDate->lock_type === LockDateType::HardLock) {
            throw new \Modules\Foundation\Exceptions\UpdateNotAllowedException('A hard lock date cannot be modified.');
        }
    }

    public function deleting(\Modules\Accounting\Models\LockDate $lockDate): void
    {
        if ($lockDate->lock_type === LockDateType::HardLock) {
            throw new \Modules\Foundation\Exceptions\UpdateNotAllowedException('A hard lock date cannot be removed.');
        }
    }

    public function saved(\Modules\Accounting\Models\LockDate $lockDate): void
    {
        $this->clearCache($lockDate);
    }

    public function deleted(\Modules\Accounting\Models\LockDate $lockDate): void
    {
        $this->clearCache($lockDate);
    }

    private function clearCache(\Modules\Accounting\Models\LockDate $lockDate): void
    {
        $typeValue = $lockDate->lock_type->value;
        Cache::forget("lock_date_{$lockDate->company_id}_{$typeValue}");
    }
}
