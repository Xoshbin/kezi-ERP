<?php

namespace Modules\Accounting\Enums\Accounting;

/**
 * Represents the state of a fiscal year in its lifecycle.
 */
enum FiscalYearState: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closing = 'closing';
    case Closed = 'closed';

    /**
     * Get the translated label for the fiscal year state.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => __('accounting::enums.fiscal_year_state.draft'),
            self::Open => __('accounting::enums.fiscal_year_state.open'),
            self::Closing => __('accounting::enums.fiscal_year_state.closing'),
            self::Closed => __('accounting::enums.fiscal_year_state.closed'),
        };
    }

    /**
     * Get the color for display in UI badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Open => 'success',
            self::Closing => 'warning',
            self::Closed => 'info',
        };
    }

    /**
     * Check if the fiscal year can be edited in this state.
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Open]);
    }

    /**
     * Check if the fiscal year can be closed in this state.
     */
    public function canClose(): bool
    {
        return $this === self::Open;
    }

    /**
     * Check if the fiscal year can be reopened in this state.
     */
    public function canReopen(): bool
    {
        return $this === self::Closed;
    }
}
