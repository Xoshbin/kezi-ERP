<?php

namespace App\Rules;

use InvalidArgumentException;
use App\Exceptions\PeriodIsLockedException;
use App\Models\Company;
use App\Services\Accounting\LockDateService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NotInLockedPeriod implements ValidationRule
{
    protected Company $company;

    public function __construct(?Company $company = null)
    {
        if ($company) {
            $this->company = $company;
        } else {
            $user = Auth::user();
            if (!$user || !$user->company) {
                throw new InvalidArgumentException('Company is required for lock date validation');
            }
            $this->company = $user->company;
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        try {
            $date = Carbon::parse($value);
            app(LockDateService::class)->enforce($this->company, $date);
        } catch (PeriodIsLockedException $e) {
            $fail($e->getMessage());
        }
    }
}
