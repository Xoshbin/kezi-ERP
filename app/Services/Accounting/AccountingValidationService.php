<?php

namespace App\Services\Accounting;

use App\Models\Company;
use App\Models\LockDate;
use Carbon\Carbon;
use Exception;

class AccountingValidationService
{
    public function ensureDateIsNotLocked(int $companyId, Carbon $date): void
    {
        $lockDate = LockDate::where('company_id', $companyId)
            ->where('lock_date', '>=', $date)
            ->exists();

        if ($lockDate) {
            throw new Exception("The date is within a locked period.");
        }
    }
}
