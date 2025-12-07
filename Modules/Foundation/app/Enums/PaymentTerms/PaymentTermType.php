<?php

namespace Modules\Foundation\Enums\PaymentTerms;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Payment Term Types
 *
 * Defines different calculation methods for payment due dates:
 * - Net: Simple days from document date (e.g., Net 30)
 * - EndOfMonth: End of month plus additional days (e.g., EOM + 15)
 * - DayOfMonth: Specific day of month (e.g., 15th of next month)
 * - Immediate: Payment due immediately
 */
enum PaymentTermType: string implements HasColor, HasIcon, HasLabel
{
    case Net = 'net';
    case EndOfMonth = 'end_of_month';
    case DayOfMonth = 'day_of_month';
    case Immediate = 'immediate';

    public function getLabel(): string
    {
        return match ($this) {
            self::Net => __('foundation::payment_terms.types.net'),
            self::EndOfMonth => __('foundation::payment_terms.types.end_of_month'),
            self::DayOfMonth => __('foundation::payment_terms.types.day_of_month'),
            self::Immediate => __('foundation::payment_terms.types.immediate'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Immediate => 'success',
            self::Net => 'primary',
            self::EndOfMonth => 'warning',
            self::DayOfMonth => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Immediate => 'heroicon-o-bolt',
            self::Net => 'heroicon-o-calendar-days',
            self::EndOfMonth => 'heroicon-o-calendar',
            self::DayOfMonth => 'heroicon-o-calendar-date-range',
        };
    }

    /**
     * Get description for this payment term type.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::Net => __('foundation::payment_terms.types.net_description'),
            self::EndOfMonth => __('foundation::payment_terms.types.end_of_month_description'),
            self::DayOfMonth => __('foundation::payment_terms.types.day_of_month_description'),
            self::Immediate => __('foundation::payment_terms.types.immediate_description'),
        };
    }

    /**
     * Check if this type requires additional configuration.
     */
    public function requiresDays(): bool
    {
        return match ($this) {
            self::Net, self::EndOfMonth => true,
            self::DayOfMonth, self::Immediate => false,
        };
    }

    /**
     * Check if this type requires day of month configuration.
     */
    public function requiresDayOfMonth(): bool
    {
        return $this === self::DayOfMonth;
    }

    /**
     * Get all types suitable for customer payment terms.
     *
     * @return array<int, self>
     */
    public static function getCustomerTypes(): array
    {
        return [
            self::Immediate,
            self::Net,
            self::EndOfMonth,
            self::DayOfMonth,
        ];
    }

    /**
     * Get all types suitable for vendor payment terms.
     *
     * @return array<int, self>
     */
    public static function getVendorTypes(): array
    {
        return [
            self::Immediate,
            self::Net,
            self::EndOfMonth,
            self::DayOfMonth,
        ];
    }

    /**
     * Get common payment term configurations.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getCommonTerms(): array
    {
        return [
            [
                'name' => __('foundation::payment_terms.common.immediate'),
                'type' => self::Immediate,
                'days' => 0,
                'percentage' => 100,
            ],
            [
                'name' => __('foundation::payment_terms.common.net_15'),
                'type' => self::Net,
                'days' => 15,
                'percentage' => 100,
            ],
            [
                'name' => __('foundation::payment_terms.common.net_30'),
                'type' => self::Net,
                'days' => 30,
                'percentage' => 100,
            ],
            [
                'name' => __('foundation::payment_terms.common.net_60'),
                'type' => self::Net,
                'days' => 60,
                'percentage' => 100,
            ],
            [
                'name' => __('foundation::payment_terms.common.eom'),
                'type' => self::EndOfMonth,
                'days' => 0,
                'percentage' => 100,
            ],
            [
                'name' => __('foundation::payment_terms.common.eom_plus_30'),
                'type' => self::EndOfMonth,
                'days' => 30,
                'percentage' => 100,
            ],
        ];
    }
}
