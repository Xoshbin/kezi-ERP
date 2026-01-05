<?php

namespace Modules\Accounting\Enums\Accounting;

use Carbon\Carbon;
use Filament\Support\Contracts\HasLabel;

enum RecurringFrequency: string implements HasLabel
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Daily => 'Daily',
            self::Weekly => 'Weekly',
            self::Monthly => 'Monthly',
            self::Yearly => 'Yearly',
        };
    }

    public function nextDate(Carbon $date, int $interval = 1): Carbon
    {
        return match ($this) {
            self::Daily => $date->copy()->addDays($interval),
            self::Weekly => $date->copy()->addWeeks($interval),
            self::Monthly => $date->copy()->addMonths($interval),
            self::Yearly => $date->copy()->addYears($interval),
        };
    }
}
