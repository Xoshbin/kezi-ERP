<?php

namespace Modules\Payment\Services\Cheques;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Modules\Payment\Enums\Cheques\ChequeStatus;
use Modules\Payment\Models\Cheque;

class ChequeMaturityService
{
    /**
     * Get cheques maturing within the specified number of days.
     */
    /**
     * Get cheques maturing within the specified number of days (Query Builder).
     */
    public function getUpcomingMaturitiesQuery(int $days = 7): \Illuminate\Database\Eloquent\Builder
    {
        return Cheque::query()
            ->whereIn('status', [ChequeStatus::HandedOver, ChequeStatus::Deposited, ChequeStatus::Draft, ChequeStatus::Printed]) // Active cheques
            ->whereDate('due_date', '>=', Carbon::today())
            ->whereDate('due_date', '<=', Carbon::today()->addDays($days))
            ->orderBy('due_date');
    }

    /**
     * Get cheques maturing within the specified number of days (Collection).
     */
    public function getUpcomingMaturities(int $days = 7): Collection
    {
        return $this->getUpcomingMaturitiesQuery($days)->get();
    }

    /**
     * Get total payable amount maturing soon.
     */
    public function getUpcomingPayableTotal(int $days, int $currencyId): string
    {
        // Logic to sum money objects would be needed here,
        // but for simple dashboard we might just sum raw DB if same currency,
        // or return collection for UI to sum.
        // Keeping it simple for now.
        return '0';
    }
}
