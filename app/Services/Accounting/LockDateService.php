<?php

namespace App\Services\Accounting;

use App\Enums\Accounting\LockDateType;
use App\Exceptions\PeriodIsLockedException;
use App\Models\Company;
use App\Models\LockDate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class LockDateService
{
    public function isPeriodLocked(Company $company, Carbon $date, string $lockType): bool
    {
        $lockedUntil = $this->getLockDateForType($company, $lockType);

        if (!$lockedUntil) {
            return false;
        }

        return $date->lte($lockedUntil);
    }

    public function enforce(Company $company, Carbon $date): void
    {
        $lockTypes = [
            LockDateType::ALL_USERS->value,
            LockDateType::HARD_LOCK->value,
        ];

        foreach ($lockTypes as $lockType) {
            if ($this->isPeriodLocked($company, $date, $lockType)) {
                $lockedDate = $this->getLockDateForType($company, $lockType);
                throw new PeriodIsLockedException("The period is locked until {$lockedDate->format('Y-m-d')}.");
            }
        }
    }

    protected function getLockDateForType(Company $company, string $lockType): ?Carbon
    {
        $cacheKey = "lock_date_{$company->id}_{$lockType}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($company, $lockType) {
            return LockDate::where('company_id', $company->id)
                ->where('lock_type', $lockType)
                ->value('locked_until');
        });
    }
}
