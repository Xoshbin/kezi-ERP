<?php

namespace Jmeryar\Inventory\Enums\Inventory;

use Filament\Support\Contracts\HasLabel;

enum LandedCostAllocationMethod: string implements HasLabel
{
    case ByQuantity = 'by_quantity';
    case ByCost = 'by_cost';
    case ByWeight = 'by_weight';
    case ByVolume = 'by_volume';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ByQuantity => 'By Quantity',
            self::ByCost => 'By Cost',
            self::ByWeight => 'By Weight',
            self::ByVolume => 'By Volume',
        };
    }
}
