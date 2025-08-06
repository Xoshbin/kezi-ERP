<?php

namespace App\Enums\Accounting;

enum JournalType: string
{
    case Sale = 'sale';
    case Purchase = 'purchase';
    case Bank = 'bank';
    case Cash = 'cash';
    case Inventory = 'inventory'; // New type for inventory valuations
    case Miscellaneous = 'miscellaneous';
}
