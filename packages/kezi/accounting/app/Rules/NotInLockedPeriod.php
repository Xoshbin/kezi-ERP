<?php

namespace Kezi\Accounting\Rules;

use App\Models\Company;
use Carbon\Carbon;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class NotInLockedPeriod implements ValidationRule
{
    protected Company $company;

    public function __construct(?Company $company = null)
    {
        if ($company) {
            $this->company = $company;
        } else {
            // Try to get company from Filament tenant context first
            $tenant = Filament::getTenant();
            if ($tenant instanceof Company) {
                $this->company = $tenant;
            } else {
                // Fallback to user's company for non-Filament contexts
                $user = Auth::user();
                if (! $user || ! $user->company) {
                    throw new InvalidArgumentException('Company is required for lock date validation');
                }
                $this->company = $user->company;
            }
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        try {
            $date = Carbon::parse($value);
            app(\Kezi\Accounting\Services\Accounting\LockDateService::class)->enforce($this->company, $date);
        } catch (\Kezi\Accounting\Exceptions\PeriodIsLockedException $e) {
            $fail($e->getMessage());
        }
    }
}
