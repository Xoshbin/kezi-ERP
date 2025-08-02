<?php

namespace App\Rules;

use App\Models\Company;
use App\Services\Accounting\LockDateService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Carbon\Carbon;

class NotInLockedPeriod implements ValidationRule
{
    protected Company $company;

    public function __construct(?Company $company = null)
    {
        $this->company = $company ?? auth()->user()->company;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        try {
            $date = Carbon::parse($value);
            app(LockDateService::class)->enforce($this->company, $date);
        } catch (\App\Exceptions\PeriodIsLockedException $e) {
            $fail($e->getMessage());
        }
    }
}
