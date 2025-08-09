<?php

namespace App\Enums\Inventory;

enum StockLocationType: string
{
    case INTERNAL = 'internal';
    case CUSTOMER = 'customer';
    case VENDOR = 'vendor';
    case INVENTORY_ADJUSTMENT = 'inventory_adjustment';

    /**
     * Get the translated label for the stock location type.
     */
    public function label(): string
    {
        return __('enums.stock_location_type.' . $this->value);
    }
}
