<?php

namespace App\Enums\Inventory;

enum ValuationMethod: string
{
    case FIFO = 'fifo';
    case LIFO = 'lifo';
    case AVCO = 'avco';
    case STANDARD = 'standard_price';
}
