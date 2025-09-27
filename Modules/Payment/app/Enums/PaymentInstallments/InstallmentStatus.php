<?php

namespace Modules\Payment\Enums\PaymentInstallments;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Payment Installment Status
 *
 * Tracks the payment status of individual installments:
 * - Pending: Not yet paid
 * - PartiallyPaid: Some amount paid but not full
 * - Paid: Fully paid
 * - Cancelled: Installment cancelled (e.g., invoice cancelled)
 */
enum InstallmentStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => __('payment_installments.status.pending'),
            self::PartiallyPaid => __('payment_installments.status.partially_paid'),
            self::Paid => __('payment_installments.status.paid'),
            self::Cancelled => __('payment_installments.status.cancelled'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::PartiallyPaid => 'info',
            self::Paid => 'success',
            self::Cancelled => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::PartiallyPaid => 'heroicon-o-banknotes',
            self::Paid => 'heroicon-o-check-circle',
            self::Cancelled => 'heroicon-o-x-circle',
        };
    }

    /**
     * Get description for this status.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::Pending => __('payment_installments.status.pending_description'),
            self::PartiallyPaid => __('payment_installments.status.partially_paid_description'),
            self::Paid => __('payment_installments.status.paid_description'),
            self::Cancelled => __('payment_installments.status.cancelled_description'),
        };
    }

    /**
     * Check if this status indicates the installment is unpaid.
     */
    public function isUnpaid(): bool
    {
        return match ($this) {
            self::Pending, self::PartiallyPaid => true,
            self::Paid, self::Cancelled => false,
        };
    }

    /**
     * Check if this status indicates the installment is fully paid.
     */
    public function isPaid(): bool
    {
        return $this === self::Paid;
    }

    /**
     * Check if this status indicates the installment is active (not cancelled).
     */
    public function isActive(): bool
    {
        return $this !== self::Cancelled;
    }

    /**
     * Get all statuses that indicate unpaid installments.
     *
     * @return array<int, self>
     */
    public static function getUnpaidStatuses(): array
    {
        return [
            self::Pending,
            self::PartiallyPaid,
        ];
    }

    /**
     * Get all statuses that indicate active installments.
     *
     * @return array<int, self>
     */
    public static function getActiveStatuses(): array
    {
        return [
            self::Pending,
            self::PartiallyPaid,
            self::Paid,
        ];
    }
}
