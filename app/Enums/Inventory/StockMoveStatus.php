<?php

namespace App\Enums\Inventory;

enum StockMoveStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case DONE = 'done';
    case CANCELLED = 'cancelled';
}
