<?php

namespace Jmeryar\Accounting\Enums\Accounting;

enum JournalType: string
{
    case Sale = 'sale';
    case Purchase = 'purchase';
    case Bank = 'bank';
    case Cash = 'cash';
    case Inventory = 'inventory'; // New type for inventory valuations
    case Miscellaneous = 'miscellaneous';

    /**
     * Get the translated label for the journal type.
     */
    public function label(): string
    {
        return __('enums.journal_type.'.$this->value);
    }
}
