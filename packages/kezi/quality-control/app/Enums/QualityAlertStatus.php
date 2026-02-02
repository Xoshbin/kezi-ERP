<?php

namespace Kezi\QualityControl\Enums;

enum QualityAlertStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => __('qualitycontrol::enums.alert_status.new'),
            self::InProgress => __('qualitycontrol::enums.alert_status.in_progress'),
            self::Resolved => __('qualitycontrol::enums.alert_status.resolved'),
            self::Closed => __('qualitycontrol::enums.alert_status.closed'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'danger',
            self::InProgress => 'warning',
            self::Resolved => 'info',
            self::Closed => 'success',
        };
    }
}
