<?php

namespace Kezi\Accounting\Enums\Deferred;

enum DeferralMethod: string
{
    case Linear = 'linear';

    // Future expansion:
    // case Days = 'days';
    // case Manual = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Linear => 'Linear (Monthly)',
        };
    }
}
