<?php

namespace Modules\Accounting\Actions\Accounting;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Enums\Accounting\FiscalPeriodState;
use Modules\Accounting\Enums\Accounting\LockDateType;
use Modules\Accounting\Exceptions\FiscalPeriodCannotBeReopenedException;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\LockDate;

class ReopenFiscalPeriodAction
{
    /**
     * Reopen a closed fiscal period.
     *
     * @throws FiscalPeriodCannotBeReopenedException
     */
    public function execute(FiscalPeriod $fiscalPeriod): FiscalPeriod
    {
        return DB::transaction(function () use ($fiscalPeriod) {
            // Validate period can be reopened
            $this->validateCanReopen($fiscalPeriod);

            // Update state
            $fiscalPeriod->update([
                'state' => FiscalPeriodState::Open,
            ]);

            // Adjust lock date to the previous closed period's end date
            $this->adjustLockDate($fiscalPeriod);

            return $fiscalPeriod->refresh();
        });
    }

    /**
     * Validate that the fiscal period can be reopened.
     *
     * @throws FiscalPeriodCannotBeReopenedException
     */
    private function validateCanReopen(FiscalPeriod $fiscalPeriod): void
    {
        // Check state
        if (! $fiscalPeriod->isClosed()) {
            throw new FiscalPeriodCannotBeReopenedException(
                __('accounting::fiscal_period.validation.not_closed')
            );
        }

        // Check parent fiscal year is not closed
        if ($fiscalPeriod->fiscalYear->isClosed()) {
            throw new FiscalPeriodCannotBeReopenedException(
                __('accounting::fiscal_period.validation.year_closed_reopen')
            );
        }
    }

    /**
     * Adjust lock date to the latest closed period before this one.
     */
    private function adjustLockDate(FiscalPeriod $fiscalPeriod): void
    {
        $companyId = $fiscalPeriod->fiscalYear->company_id;

        // Find the latest closed period BEFORE this one ends
        $previousClosedPeriod = FiscalPeriod::whereHas('fiscalYear', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
            ->where('state', FiscalPeriodState::Closed)
            ->where('end_date', '<', $fiscalPeriod->start_date)
            ->orderBy('end_date', 'desc')
            ->first();

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
            // No previous closed period, remove the lock
            LockDate::where('company_id', $companyId)
                ->where('lock_type', LockDateType::AllUsers->value)
                ->delete();
        }

        // Clear cache
        Cache::forget("lock_date_{$companyId}_".LockDateType::AllUsers->value);
    }
}
