<?php

namespace Modules\Accounting\Services\Accounting;

use Carbon\Carbon;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Modules\Accounting\Models\LockDate;
use Modules\Accounting\Enums\Accounting\LockDateType;

class LockDateService
{
    public function isPeriodLocked(Company $company, Carbon $date, string $lockType): bool
    {
        $lockedUntil = $this->getLockDateForType($company, $lockType);

        if (! $lockedUntil) {
            return false;
        }

        return $date->lt($lockedUntil);
    }

    public function enforce(Company $company, Carbon $date): void
    {
        $lockTypes = [
            LockDateType::AllUsers->value,
            LockDateType::HardLock->value,
        ];

        foreach ($lockTypes as $lockType) {
            $lockedDate = $this->getLockDateForType($company, $lockType);
            if ($lockedDate && $date->lte($lockedDate)) {
                throw new \Modules\Accounting\Exceptions\PeriodIsLockedException("The period is locked until {$lockedDate->format('Y-m-d')}.");
            }
        }
    }

    protected function getLockDateForType(Company $company, string $lockType): ?Carbon
    {
        $cacheKey = "lock_date_{$company->id}_{$lockType}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($company, $lockType) {
            $date = LockDate::where('company_id', $company->id)
                ->where('lock_type', $lockType)
                ->value('locked_until');

            return $date ? Carbon::parse($date) : null;
        });
    }
}
