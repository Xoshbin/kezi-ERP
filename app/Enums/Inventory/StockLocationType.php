<?php

namespace App\Enums\Inventory;

enum StockLocationType: string
{
    case Internal = 'internal';
    case Customer = 'Customer';
    case Vendor = 'vendor';
    case InventoryAdjustment = 'inventory_adjustment';

    /**
     * Get the translated label for the stock location type.
     */
    public function label(): string
    {
        return __('enums.stock_location_type.'.$this->value);
    }
}
