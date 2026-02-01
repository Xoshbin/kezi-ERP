<?php

namespace Jmeryar\Manufacturing\Enums;

enum BOMType: string
{
    case Normal = 'normal';
    case Kit = 'kit';
    case Phantom = 'phantom';

    public function label(): string
    {
        return match ($this) {
            self::Normal => __('manufacturing::enums.bom_type.normal'),
            self::Kit => __('manufacturing::enums.bom_type.kit'),
            self::Phantom => __('manufacturing::enums.bom_type.phantom'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Normal => __('manufacturing::enums.bom_type.normal_description'),
            self::Kit => __('manufacturing::enums.bom_type.kit_description'),
            self::Phantom => __('manufacturing::enums.bom_type.phantom_description'),
        };
    }
}
