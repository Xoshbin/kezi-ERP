<?php

namespace Kezi\Inventory\Enums\Inventory;

enum SerialNumberStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Returned = 'returned';
    case Defective = 'defective';

    /**
     * Get the translated label for the serial number status.
     */
    public function label(): string
    {
        return __('inventory.serial_status_'.$this->value);
    }
}
