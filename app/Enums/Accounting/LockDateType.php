<?php

namespace App\Enums\Accounting;

enum LockDateType: string
{
    case TAX_RETURN = 'tax_return_date';
    case ALL_USERS = 'everything_date';
    case HARD_LOCK = 'hard_lock';

    public function getLabel(): string
    {
        return match ($this) {
            self::TAX_RETURN => 'Tax Return Lock',
            self::ALL_USERS => 'All Users Lock',
            self::HARD_LOCK => 'Hard Lock (Immutable)',
        };
    }
}
