<?php

namespace App\Enums\Inventory;

enum StockMoveType: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';
    case InternalTransfer = 'internal_transfer';
    case Adjustment = 'adjustment';

    /**
     * Get the translated label for the stock move type.
     */
    public function label(): string
    {
        return __('enums.stock_move_type.'.$this->value);
    }
}
