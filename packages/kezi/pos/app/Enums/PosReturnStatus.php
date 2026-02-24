<?php

namespace Kezi\Pos\Enums;

enum PosReturnStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('pos::pos_return.status.draft'),
            self::PendingApproval => __('pos::pos_return.status.pending_approval'),
            self::Approved => __('pos::pos_return.status.approved'),
            self::Rejected => __('pos::pos_return.status.rejected'),
            self::Processing => __('pos::pos_return.status.processing'),
            self::Completed => __('pos::pos_return.status.completed'),
            self::Cancelled => __('pos::pos_return.status.cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingApproval => 'warning',
            self::Approved => 'info',
            self::Rejected => 'danger',
            self::Processing => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'gray',
        };
    }
}
