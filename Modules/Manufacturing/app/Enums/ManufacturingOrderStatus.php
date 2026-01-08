<?php

namespace Modules\Manufacturing\Enums;

enum ManufacturingOrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('manufacturing::enums.mo_status.draft'),
            self::Confirmed => __('manufacturing::enums.mo_status.confirmed'),
            self::InProgress => __('manufacturing::enums.mo_status.in_progress'),
            self::Done => __('manufacturing::enums.mo_status.done'),
            self::Cancelled => __('manufacturing::enums.mo_status.cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Confirmed => 'info',
            self::InProgress => 'warning',
            self::Done => 'success',
            self::Cancelled => 'danger',
        };
    }
}
