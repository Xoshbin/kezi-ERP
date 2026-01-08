<?php

namespace Modules\QualityControl\Enums;

enum QualityTriggerFrequency: string
{
    case PerOperation = 'per_operation';
    case PerProduct = 'per_product';
    case PerQuantity = 'per_quantity';

    public function label(): string
    {
        return match ($this) {
            self::PerOperation => __('quality::enums.trigger_frequency.per_operation'),
            self::PerProduct => __('quality::enums.trigger_frequency.per_product'),
            self::PerQuantity => __('quality::enums.trigger_frequency.per_quantity'),
        };
    }
}
