<?php

namespace App\Enums\RecurringInvoice;

enum RecurringFrequency: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => __('enums.recurring_frequency.monthly'),
            self::Quarterly => __('enums.recurring_frequency.quarterly'),
            self::Yearly => __('enums.recurring_frequency.yearly'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Monthly => __('enums.recurring_frequency.monthly_description'),
            self::Quarterly => __('enums.recurring_frequency.quarterly_description'),
            self::Yearly => __('enums.recurring_frequency.yearly_description'),
        };
    }

    /**
     * Get the number of months for this frequency.
     */
    public function getMonthsInterval(): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Quarterly => 3,
            self::Yearly => 12,
        };
    }

    /**
     * Get all available frequencies as options for forms.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
