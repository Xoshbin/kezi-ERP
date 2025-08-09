<?php

namespace App\Enums\Accounting;

enum LockDateType: string
{
    case TAX_RETURN = 'tax_return_date';
    case ALL_USERS = 'everything_date';
    case HARD_LOCK = 'hard_lock';

    /**
     * Get the translated label for the lock date type.
     */
    public function label(): string
    {
        return __('enums.lock_date_type.' . $this->value);
    }
}
