<?php

namespace App\Rules;

use App\Models\Company;
use App\Services\Accounting\LockDateService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Filament\Facades\Filament;

class NotInLockedPeriod implements ValidationRule
{
    protected Company $company;

    public function __construct(?Company $company = null)
    {
        if ($company) {
            $this->company = $company;
        } else {
            // Try to get company from Filament tenant first
            $tenant = Filament::getTenant();
            if ($tenant) {
                $this->company = $tenant;
            } else {
                // Fallback to user's first company for non-Filament contexts
                $user = Auth::user();
                if (!$user || $user->companies->isEmpty()) {
                    throw new \InvalidArgumentException('Company is required for lock date validation');
                }
                $this->company = $user->companies->first();
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
            app(LockDateService::class)->enforce($this->company, $date);
        } catch (\App\Exceptions\PeriodIsLockedException $e) {
            $fail($e->getMessage());
        }
    }
}
