<?php

namespace App\Enums\Inventory;

enum StockMoveType: string
{
    case INCOMING = 'incoming';
    case OUTGOING = 'outgoing';
    case INTERNAL_TRANSFER = 'internal_transfer';
    case ADJUSTMENT = 'adjustment';

    /**
     * Get the translated label for the stock move type.
     */
    public function label(): string
    {
        return __('enums.stock_move_type.' . $this->value);
    }
}
