<?php

namespace Jmeryar\Accounting\Enums\Accounting;

enum LockDateType: string
{
    case TaxReturn = 'tax_return_date';
    case AllUsers = 'everything_date';
    case HardLock = 'hard_lock';

    /**
     * Get the translated label for the lock date type.
     */
    public function label(): string
    {
        return __('enums.lock_date_type.'.$this->value);
    }
}
