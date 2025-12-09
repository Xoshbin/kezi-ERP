<?php

namespace Modules\Inventory\Enums\Inventory;

enum StockPickingType: string
{
    case Receipt = 'receipt';
    case Delivery = 'delivery';
    case Internal = 'internal';

    public function label(): string
    {
        return __('enums.stock_picking_type.' . $this->value);
    }
}
