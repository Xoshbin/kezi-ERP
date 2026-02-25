<?php

namespace Kezi\Pos\Enums;

enum PosOrderStatus: string
{
    case Draft = 'draft';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('pos::pos_order.draft'),
            self::Paid => __('pos::pos_order.paid'),
            self::Cancelled => __('pos::pos_order.cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Paid => 'success',
            self::Cancelled => 'danger',
        };
    }
}
