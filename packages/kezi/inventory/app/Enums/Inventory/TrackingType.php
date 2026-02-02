<?php

namespace Kezi\Inventory\Enums\Inventory;

enum TrackingType: string
{
    case None = 'none';
    case Lot = 'lot';
    case Serial = 'serial';

    /**
     * Get the translated label for the tracking type.
     */
    public function label(): string
    {
        return __('inventory.tracking_type_'.$this->value);
    }
}
