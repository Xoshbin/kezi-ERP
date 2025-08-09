<?php

namespace App\Enums\Inventory;

enum StockMoveStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case DONE = 'done';
    case CANCELLED = 'cancelled';

    /**
     * Get the translated label for the stock move status.
     */
    public function label(): string
    {
        return __('enums.stock_move_status.' . $this->value);
    }
}
