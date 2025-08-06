<?php

namespace App\Enums\Inventory;

enum StockMoveType: string
{
    case INCOMING = 'incoming';
    case OUTGOING = 'outgoing';
    case INTERNAL_TRANSFER = 'internal_transfer';
    case ADJUSTMENT = 'adjustment';
}
