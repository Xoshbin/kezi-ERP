<?php

namespace App\Enums\RecurringInvoice;

enum RecurringStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('enums.recurring_status.active'),
            self::Paused => __('enums.recurring_status.paused'),
            self::Completed => __('enums.recurring_status.completed'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Active => __('enums.recurring_status.active_description'),
            self::Paused => __('enums.recurring_status.paused_description'),
            self::Completed => __('enums.recurring_status.completed_description'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Paused => 'warning',
            self::Completed => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Active => 'heroicon-o-play',
            self::Paused => 'heroicon-o-pause',
            self::Completed => 'heroicon-o-check-circle',
        };
    }

    /**
     * Get all available statuses as options for forms.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }

    /**
     * Get statuses that allow generation.
     */
    public static function generationAllowed(): array
    {
        return [self::Active];
    }
}
