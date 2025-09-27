<?php

namespace Modules\Inventory\Enums\Inventory;

enum StockPickingState: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Assigned = 'assigned';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('enums.stock_picking_state.' . $this->value);
    }
}

