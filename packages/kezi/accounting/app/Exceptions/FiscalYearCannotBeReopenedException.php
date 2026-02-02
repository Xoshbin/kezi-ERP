<?php

namespace Kezi\Accounting\Exceptions;

use Exception;

class FiscalYearCannotBeReopenedException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'Fiscal year cannot be reopened.')
    {
        parent::__construct($message);
    }
}
