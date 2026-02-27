<?php

namespace Kezi\Pos\Enums;

enum PosSessionStatus: string
{
    case Opened = 'opened';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Opened => __('pos::pos_session.status_opened'),
            self::Closed => __('pos::pos_session.status_closed'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Opened => 'success',
            self::Closed => 'gray',
        };
    }
}
