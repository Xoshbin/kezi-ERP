<?php

namespace Jmeryar\Manufacturing\Enums;

enum WorkOrderStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('manufacturing::enums.wo_status.pending'),
            self::Ready => __('manufacturing::enums.wo_status.ready'),
            self::InProgress => __('manufacturing::enums.wo_status.in_progress'),
            self::Done => __('manufacturing::enums.wo_status.done'),
            self::Cancelled => __('manufacturing::enums.wo_status.cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Ready => 'info',
            self::InProgress => 'warning',
            self::Done => 'success',
            self::Cancelled => 'danger',
        };
    }
}
