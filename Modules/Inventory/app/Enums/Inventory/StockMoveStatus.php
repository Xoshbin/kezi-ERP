<?php

namespace Modules\Inventory\Enums\Inventory;

enum StockMoveStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Done = 'done';
    case Cancelled = 'cancelled';

    /**
     * Get the translated label for the stock move status.
     */
    public function label(): string
    {
        return __('enums.stock_move_status.'.$this->value);
    }
}
