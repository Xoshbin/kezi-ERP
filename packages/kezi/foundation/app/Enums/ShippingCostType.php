<?php

namespace Kezi\Foundation\Enums;

use Filament\Support\Contracts\HasLabel;

enum ShippingCostType: string implements HasLabel
{
    case Freight = 'freight';
    case Insurance = 'insurance';
    case CustomsDuty = 'customs_duty';
    case Handling = 'handling';
    case PortCharges = 'port_charges';

    public function getLabel(): string
    {
        return match ($this) {
            self::Freight => __('foundation::enums.shipping_cost_type.freight'),
            self::Insurance => __('foundation::enums.shipping_cost_type.insurance'),
            self::CustomsDuty => __('foundation::enums.shipping_cost_type.customs_duty'),
            self::Handling => __('foundation::enums.shipping_cost_type.handling'),
            self::PortCharges => __('foundation::enums.shipping_cost_type.port_charges'),
        };
    }
}
