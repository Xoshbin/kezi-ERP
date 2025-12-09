<?php

namespace Modules\Accounting\Exceptions;

use Exception;

class PeriodIsLockedException extends Exception
{
    public function __construct(string $message = 'The accounting period is locked and cannot be modified.')
    {
        parent::__construct($message);
    }
}
