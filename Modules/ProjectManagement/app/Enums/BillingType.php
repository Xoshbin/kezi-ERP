<?php

namespace Modules\ProjectManagement\Enums;

enum BillingType: string
{
    case FixedPrice = 'fixed_price';
    case TimeAndMaterials = 'time_and_materials';
    case Milestone = 'milestone';

    public function label(): string
    {
        return match ($this) {
            self::FixedPrice => 'Fixed Price',
            self::TimeAndMaterials => 'Time & Materials',
            self::Milestone => 'Milestone',
        };
    }
}
