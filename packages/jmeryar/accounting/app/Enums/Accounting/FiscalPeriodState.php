<?php

namespace Jmeryar\Accounting\Enums\Accounting;

/**
 * Represents the state of a fiscal period within a fiscal year.
 */
enum FiscalPeriodState: string
{
    case Open = 'open';
    case Closed = 'closed';

    /**
     * Get the translated label for the fiscal period state.
     */
    public function label(): string
    {
        return match ($this) {
            self::Open => __('accounting::enums.fiscal_period_state.open'),
            self::Closed => __('accounting::enums.fiscal_period_state.closed'),
        };
    }

    /**
     * Get the color for display in UI badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::Open => 'success',
            self::Closed => 'info',
        };
    }

    /**
     * Check if the period can be closed in this state.
     */
    public function canClose(): bool
    {
        return $this === self::Open;
    }
}
