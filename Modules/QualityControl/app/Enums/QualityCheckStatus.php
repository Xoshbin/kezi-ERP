<?php

namespace Modules\QualityControl\Enums;

enum QualityCheckStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Passed = 'passed';
    case Failed = 'failed';
    case ConditionallyAccepted = 'conditionally_accepted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('quality::enums.check_status.draft'),
            self::InProgress => __('quality::enums.check_status.in_progress'),
            self::Passed => __('quality::enums.check_status.passed'),
            self::Failed => __('quality::enums.check_status.failed'),
            self::ConditionallyAccepted => __('quality::enums.check_status.conditionally_accepted'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::InProgress => 'warning',
            self::Passed => 'success',
            self::Failed => 'danger',
            self::ConditionallyAccepted => 'info',
        };
    }
}
