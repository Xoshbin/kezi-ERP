<?php

namespace Modules\Inventory\Enums\Inventory;

enum StockLocationType: string
{
    case Internal = 'internal';
    case Customer = 'customer';
    case Vendor = 'vendor';
    case InventoryAdjustment = 'inventory_adjustment';
    case Transit = 'transit';
    case Scrap = 'scrap';

    /**
     * Get the translated label for the stock location type.
     */
    public function label(): string
    {
        return __('enums.stock_location_type.'.$this->value);
    }
}
