<?php

namespace Jmeryar\Accounting\Enums\Accounting;

use Filament\Support\Contracts\HasLabel;

enum RecurringTargetType: string implements HasLabel
{
    case JournalEntry = 'journal_entry';
    case Invoice = 'invoice';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::JournalEntry => 'Journal Entry',
            self::Invoice => 'Invoice',
        };
    }
}
