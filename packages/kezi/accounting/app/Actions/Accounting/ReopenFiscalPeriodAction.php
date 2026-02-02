<?php

declare(strict_types=1);

namespace Kezi\Accounting\Actions\Accounting;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Enums\Accounting\FiscalPeriodState;
use Kezi\Accounting\Enums\Accounting\LockDateType;
use Kezi\Accounting\Exceptions\FiscalPeriodCannotBeReopenedException;
use Kezi\Accounting\Models\FiscalPeriod;
use Kezi\Accounting\Models\LockDate;

/**
 * Reopens a closed fiscal period and adjusts the lock date.
 *
 * When a period is reopened, the system finds the most recent remaining
 * closed period and sets the lock date to its end date. If no closed
 * periods remain, the lock is removed entirely.
 */
final class ReopenFiscalPeriodAction
{
    /**
     * Execute the action to reopen a closed fiscal period.
     *
     * @throws FiscalPeriodCannotBeReopenedException When validation fails
     */
    public function execute(FiscalPeriod $fiscalPeriod): FiscalPeriod
    {
        return DB::transaction(function () use ($fiscalPeriod): FiscalPeriod {
            $this->ensureCanReopen($fiscalPeriod);

            $fiscalPeriod->update([
                'state' => FiscalPeriodState::Open,
            ]);

            $this->adjustLockDate($fiscalPeriod);

            return $fiscalPeriod->refresh();
        });
    }

    /**
     * Ensure the fiscal period meets all requirements for reopening.
     *
     * @throws FiscalPeriodCannotBeReopenedException
     */
    private function ensureCanReopen(FiscalPeriod $fiscalPeriod): void
    {
        if (! $fiscalPeriod->isClosed()) {
            throw new FiscalPeriodCannotBeReopenedException(
                __('accounting::fiscal_period.validation.not_closed')
            );
        }

        if ($fiscalPeriod->fiscalYear->isClosed()) {
            throw new FiscalPeriodCannotBeReopenedException(
                __('accounting::fiscal_period.validation.year_closed_reopen')
            );
        }
    }

    /**
     * Adjust the lock date to the previous closed period's end date.
     *
     * If no closed periods remain, the lock is removed entirely.
     */
    private function adjustLockDate(FiscalPeriod $fiscalPeriod): void
    {
        $companyId = $fiscalPeriod->fiscalYear->company_id;

        $previousClosedPeriod = $this->findPreviousClosedPeriod($fiscalPeriod, $companyId);

        if ($previousClosedPeriod) {
            LockDate::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'lock_type' => LockDateType::AllUsers->value,
                ],
                [
                    'locked_until' => $previousClosedPeriod->end_date,
                ]
            );
        } else {
            LockDate::query()
                ->where('company_id', $companyId)
                ->where('lock_type', LockDateType::AllUsers->value)
                ->delete();
        }

        $this->clearLockDateCache($companyId);
    }

    /**
     * Find the most recent closed period before the given period.
     */
    private function findPreviousClosedPeriod(FiscalPeriod $fiscalPeriod, int $companyId): ?FiscalPeriod
    {
        return FiscalPeriod::query()
            ->whereHas('fiscalYear', fn (Builder $query) => $query->where('company_id', $companyId))
            ->where('state', FiscalPeriodState::Closed)
            ->where('end_date', '<', $fiscalPeriod->start_date)
            ->orderByDesc('end_date')
            ->first();
    }

    /**
     * Clear the cached lock date for a company.
     */
    private function clearLockDateCache(int $companyId): void
    {
        $cacheKey = "lock_date_{$companyId}_".LockDateType::AllUsers->value;
        Cache::forget($cacheKey);
    }
}
