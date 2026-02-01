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
            self::Freight => __('Freight'),
            self::Insurance => __('Insurance'),
            self::CustomsDuty => __('Customs Duty'),
            self::Handling => __('Handling'),
            self::PortCharges => __('Port Charges'),
        };
    }
}
