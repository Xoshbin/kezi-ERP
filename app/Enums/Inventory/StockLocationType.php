<?php

namespace App\Enums\Inventory;

enum StockLocationType: string
{
    case INTERNAL = 'internal';
    case CUSTOMER = 'customer';
    case VENDOR = 'vendor';
    case INVENTORY_ADJUSTMENT = 'inventory_adjustment';
}
