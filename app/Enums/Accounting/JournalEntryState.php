<?php

namespace App\Enums\Accounting;

enum JournalEntryState: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Reversed = 'reversed';
}
