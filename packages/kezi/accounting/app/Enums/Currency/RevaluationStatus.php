<?php

namespace Kezi\Accounting\Enums\Currency;

/**
 * Defines the status of a currency revaluation.
 */
enum RevaluationStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Reversed = 'reversed';

    /**
     * Get the translated label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => __('accounting::enums.revaluation_status.draft'),
            self::Posted => __('accounting::enums.revaluation_status.posted'),
            self::Reversed => __('accounting::enums.revaluation_status.reversed'),
        };
    }

    /**
     * Get the color for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Posted => 'success',
            self::Reversed => 'danger',
        };
    }

    /**
     * Check if the revaluation can be modified.
     */
    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Check if the revaluation can be posted.
     */
    public function canBePosted(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Check if the revaluation can be reversed.
     */
    public function canBeReversed(): bool
    {
        return $this === self::Posted;
    }
}
